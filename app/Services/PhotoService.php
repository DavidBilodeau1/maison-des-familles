<?php

namespace App\Services;

use App\Models\Family;
use App\Models\PhotoSelection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class PhotoService
{
    protected $photosBasePath;

    protected $uploadsPath;

    protected $finalChoicesPath;

    public function __construct()
    {
        $this->photosBasePath = storage_path('app/photos');
        $this->uploadsPath = $this->photosBasePath.'/uploads';
        $this->finalChoicesPath = $this->photosBasePath.'/final_choices';
    }

    public function getFamilyPhotos(Family $family)
    {
        if ($this->webhookUrl()) {
            $response = $this->webhook('get', '/list-photos/'.$family->directory_name);

            return $response->successful() ? $response->json('files', []) : [];
        }

        $familyDir = $this->uploadsPath.'/'.$family->directory_name;

        if (! File::exists($familyDir)) {
            return [];
        }

        $photos = [];
        foreach (File::files($familyDir) as $file) {
            if ($this->isImageFile($file)) {
                $photos[] = $file->getFilename();
            }
        }

        return $photos;
    }

    public function getFinalChoicesPhotos(Family $family)
    {
        if ($this->webhookUrl()) {
            $response = $this->webhook('get', '/list-final/'.$family->directory_name);

            return $response->successful() ? $response->json('files', []) : [];
        }

        $familyDir = $this->finalChoicesPath.'/'.$family->directory_name;

        if (! File::exists($familyDir)) {
            return [];
        }

        $photos = [];
        foreach (File::files($familyDir) as $file) {
            if ($this->isImageFile($file)) {
                $photos[] = $file->getFilename();
            }
        }

        return $photos;
    }

    public function syncFamilyPhotos(Family $family)
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
            ->whereNotIn('photo_filename', $uploadsPhotos)
            ->delete();

        $family->photoSelections()
            ->where('location', 'final_choices')
            ->whereNotIn('photo_filename', $finalPhotos)
            ->delete();
    }

    public function moveSelectedPhotos(Family $family)
    {
        $selectedPhotos = $family->selectedPhotos;

        if ($selectedPhotos->isEmpty()) {
            return;
        }

        if ($this->webhookUrl()) {
            $this->webhook('post', '/move-photos', [
                'directory_name' => $family->directory_name,
                'filenames' => $selectedPhotos->pluck('photo_filename')->toArray(),
            ]);

            $selectedPhotos->each(fn ($s) => $s->update(['location' => 'final_choices']));

            return;
        }

        $sourceDir = $this->uploadsPath.'/'.$family->directory_name;
        $destDir = $this->finalChoicesPath.'/'.$family->directory_name;

        if (! File::exists($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }

        foreach ($selectedPhotos as $selection) {
            $sourcePath = $sourceDir.'/'.$selection->photo_filename;
            $destPath = $destDir.'/'.$selection->photo_filename;

            if (File::exists($sourcePath)) {
                File::move($sourcePath, $destPath);
                $selection->update(['location' => 'final_choices']);
            }
        }
    }

    public function createFamilyDirectory($directoryName)
    {
        if ($this->webhookUrl()) {
            $this->webhook('post', '/create-directory', [
                'directory_name' => $directoryName,
            ]);

            return $this->uploadsPath.'/'.$directoryName;
        }

        if (config('photoshoot.storage.photos_url')) {
            return $this->uploadsPath.'/'.$directoryName;
        }

        $familyDir = $this->uploadsPath.'/'.$directoryName;

        if (! File::exists($familyDir)) {
            File::makeDirectory($familyDir, 0755, true);
        }

        return $familyDir;
    }

    public function getPhotoUrl($familyDirectoryName, $filename)
    {
        $baseUrl = config('photoshoot.storage.photos_url');

        if ($baseUrl) {
            return rtrim($baseUrl, '/').'/'.$familyDirectoryName.'/'.$filename;
        }

        return '/photos/'.$familyDirectoryName.'/'.$filename;
    }

    public function isExternalStorage(): bool
    {
        return (bool) config('photoshoot.storage.photos_url');
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

    protected function isImageFile($file)
    {
        // Skip macOS resource fork files (._filename)
        if (str_starts_with($file->getFilename(), '.')) {
            return false;
        }

        return in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }
}
