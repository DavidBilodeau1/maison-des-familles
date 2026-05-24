<?php

namespace App\Services;

use App\Models\Family;
use App\Models\PhotoSelection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class PhotoService
{
    protected string $uploadsPath;

    protected string $finalChoicesPath;

    public function __construct()
    {
        $this->uploadsPath = storage_path('app/photos/uploads');
        $this->finalChoicesPath = storage_path('app/photos/final_choices');
    }

    // ── Storage driver ───────────────────────────────────────────────────────

    public function storageDriver(): string
    {
        if (config('filesystems.disks.r2.bucket')) {
            return 'r2';
        }
        if ($this->webhookUrl()) {
            return 'webhook';
        }

        return 'local';
    }

    public function isExternalStorage(): bool
    {
        return in_array($this->storageDriver(), ['r2', 'webhook'])
            || (bool) config('photoshoot.storage.photos_url');
    }

    // ── List photos ──────────────────────────────────────────────────────────

    public function getFamilyPhotos(Family $family): array
    {
        return match ($this->storageDriver()) {
            'r2' => $this->listR2Images("uploads/{$family->directory_name}"),
            'webhook' => $this->webhookList('/list-photos/'.$family->directory_name),
            default => $this->listLocalImages($this->uploadsPath.'/'.$family->directory_name),
        };
    }

    public function getFinalChoicesPhotos(Family $family): array
    {
        return match ($this->storageDriver()) {
            'r2' => $this->listR2Images("final_choices/{$family->directory_name}"),
            'webhook' => $this->webhookList('/list-final/'.$family->directory_name),
            default => $this->listLocalImages($this->finalChoicesPath.'/'.$family->directory_name),
        };
    }

    // ── Sync ─────────────────────────────────────────────────────────────────

    public function syncFamilyPhotos(Family $family): void
    {
        $uploadsPhotos = $this->getFamilyPhotos($family);
        $finalPhotos = $this->getFinalChoicesPhotos($family);
        $existingPhotos = $family->photoSelections()->pluck('photo_filename')->toArray();

        foreach ($uploadsPhotos as $photo) {
            if (! in_array($photo, $existingPhotos)) {
                PhotoSelection::create([
                    'family_id' => $family->id,
                    'photo_filename' => $photo,
                    'is_selected' => false,
                    'location' => 'uploads',
                ]);
            }
        }

        foreach ($finalPhotos as $photo) {
            if (! in_array($photo, $existingPhotos)) {
                PhotoSelection::create([
                    'family_id' => $family->id,
                    'photo_filename' => $photo,
                    'is_selected' => true,
                    'location' => 'final_choices',
                ]);
            }
        }

        $family->photoSelections()
            ->where('location', 'uploads')
            ->where('is_selected', false)
            ->whereNotIn('photo_filename', $uploadsPhotos)
            ->delete();

        $family->photoSelections()
            ->where('location', 'final_choices')
            ->where('is_selected', false)
            ->whereNotIn('photo_filename', $finalPhotos)
            ->delete();
    }

    // ── Move selected photos to final_choices ────────────────────────────────

    public function moveSelectedPhotos(Family $family): void
    {
        $selectedPhotos = $family->selectedPhotos;

        if ($selectedPhotos->isEmpty()) {
            return;
        }

        switch ($this->storageDriver()) {

            case 'r2':
                foreach ($selectedPhotos as $selection) {
                    $src = "uploads/{$family->directory_name}/{$selection->photo_filename}";
                    $dst = "final_choices/{$family->directory_name}/{$selection->photo_filename}";
                    if (Storage::disk('r2')->exists($src)) {
                        Storage::disk('r2')->copy($src, $dst);
                        Storage::disk('r2')->delete($src);
                    }
                    $selection->update(['location' => 'final_choices']);
                }
                break;

            case 'webhook':
                $this->webhook('post', '/move-photos', [
                    'directory_name' => $family->directory_name,
                    'filenames' => $selectedPhotos->pluck('photo_filename')->toArray(),
                ]);
                $selectedPhotos->each(fn ($s) => $s->update(['location' => 'final_choices']));
                break;

            default:
                $sourceDir = $this->uploadsPath.'/'.$family->directory_name;
                $destDir = $this->finalChoicesPath.'/'.$family->directory_name;

                if (! File::exists($destDir)) {
                    File::makeDirectory($destDir, 0755, true);
                }

                foreach ($selectedPhotos as $selection) {
                    $src = $sourceDir.'/'.$selection->photo_filename;
                    $dst = $destDir.'/'.$selection->photo_filename;
                    if (File::exists($src)) {
                        File::move($src, $dst);
                    }
                    $selection->update(['location' => 'final_choices']);
                }
        }
    }

    // ── Create family directory ───────────────────────────────────────────────

    public function createFamilyDirectory(string $directoryName): string
    {
        switch ($this->storageDriver()) {

            case 'r2':
                // R2 has no real directories — place a hidden marker so the
                // folder appears in the dashboard and the prefix is valid.
                foreach (['uploads', 'final_choices'] as $bucket) {
                    $marker = "{$bucket}/{$directoryName}/.keep";
                    if (! Storage::disk('r2')->exists($marker)) {
                        Storage::disk('r2')->put($marker, '');
                    }
                }

                return "uploads/{$directoryName}";

            case 'webhook':
                $this->webhook('post', '/create-directory', [
                    'directory_name' => $directoryName,
                ]);

                return $this->uploadsPath.'/'.$directoryName;

            default:
                foreach ([$this->uploadsPath, $this->finalChoicesPath] as $base) {
                    $dir = $base.'/'.$directoryName;
                    if (! File::exists($dir)) {
                        File::makeDirectory($dir, 0755, true);
                    }
                }

                return $this->uploadsPath.'/'.$directoryName;
        }
    }

    // ── Thumbnail URL ─────────────────────────────────────────────────────────

    public function getThumbnailUrl(string $familyDirectoryName, string $filename, string $location = 'uploads'): string
    {
        if ($this->storageDriver() === 'r2') {
            return rtrim(config('filesystems.disks.r2.url'), '/')."/thumbs/{$familyDirectoryName}/{$filename}";
        }

        $thumbPath = storage_path("app/photos/thumbs/{$familyDirectoryName}/{$filename}");
        if (file_exists($thumbPath)) {
            return route('photos.serve.thumb', ['family' => $familyDirectoryName, 'filename' => $filename]);
        }

        // No thumbnail generated yet — fall back to original
        return $this->getPhotoUrl($familyDirectoryName, $filename, $location);
    }

    public function thumbnailExists(string $familyDirectoryName, string $filename): bool
    {
        if ($this->storageDriver() === 'r2') {
            return Storage::disk('r2')->exists("thumbs/{$familyDirectoryName}/{$filename}");
        }

        return file_exists(storage_path("app/photos/thumbs/{$familyDirectoryName}/{$filename}"));
    }

    public function storeThumbnail(string $familyDirectoryName, string $filename, string $imageData): void
    {
        if ($this->storageDriver() === 'r2') {
            Storage::disk('r2')->put("thumbs/{$familyDirectoryName}/{$filename}", $imageData);

            return;
        }

        $dir = storage_path("app/photos/thumbs/{$familyDirectoryName}");
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($dir.'/'.$filename, $imageData);
    }

    // ── Photo URL ─────────────────────────────────────────────────────────────

    public function getPhotoUrl(string $familyDirectoryName, string $filename, string $location = 'uploads'): string
    {
        if ($this->storageDriver() === 'r2') {
            $prefix = $location === 'final_choices' ? 'final_choices' : 'uploads';

            return rtrim(config('filesystems.disks.r2.url'), '/')."/{$prefix}/{$familyDirectoryName}/{$filename}";
        }

        $baseUrl = config('photoshoot.storage.photos_url');
        if ($baseUrl) {
            $prefix = $location === 'final_choices' ? 'final' : 'photos';

            return rtrim($baseUrl, '/')."/{$prefix}/{$familyDirectoryName}/{$filename}";
        }

        $route = $location === 'final_choices' ? 'photos.serve.final' : 'photos.serve';

        return route($route, ['family' => $familyDirectoryName, 'filename' => $filename]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    protected function listR2Images(string $prefix): array
    {
        $files = Storage::disk('r2')->files($prefix);
        $images = [];
        foreach ($files as $path) {
            $filename = basename($path);
            if (str_starts_with($filename, '.')) {
                continue;
            }
            if (in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $images[] = $filename;
            }
        }

        return $images;
    }

    protected function listLocalImages(string $dir): array
    {
        if (! File::exists($dir)) {
            return [];
        }
        $photos = [];
        foreach (File::files($dir) as $file) {
            if ($this->isImageFile($file)) {
                $photos[] = $file->getFilename();
            }
        }

        return $photos;
    }

    protected function webhookList(string $endpoint): array
    {
        $response = $this->webhook('get', $endpoint);

        return $response->successful() ? $response->json('files', []) : [];
    }

    protected function webhookUrl(): ?string
    {
        return config('photoshoot.storage.webhook_url') ?: null;
    }

    protected function webhook(string $method, string $endpoint, array $data = [])
    {
        $request = Http::withToken(config('photoshoot.storage.webhook_secret'))
            ->timeout(10);
        $url = rtrim($this->webhookUrl(), '/').$endpoint;

        return $method === 'post' ? $request->post($url, $data) : $request->get($url);
    }

    protected function isImageFile($file): bool
    {
        if (str_starts_with($file->getFilename(), '.')) {
            return false;
        }

        return in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }
}
