<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Services\PhotoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateThumbnails extends Command
{
    protected $signature = 'photos:generate-thumbs
                            {--family= : Directory name of a specific family to process}
                            {--force   : Regenerate thumbnails even if they already exist}
                            {--width=800  : Maximum thumbnail width in pixels}
                            {--quality=80 : JPEG output quality (1–100)}';

    protected $description = 'Generate resized thumbnails for family photos and store them alongside originals';

    public function __construct(protected PhotoService $photoService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $driver = $this->photoService->storageDriver();
        $this->info("Storage driver: {$driver}");

        if ($driver === 'webhook') {
            $this->error('Thumbnail generation is not supported for the webhook storage driver.');

            return 1;
        }

        $families = $this->option('family')
            ? Family::where('directory_name', $this->option('family'))->get()
            : Family::all();

        if ($families->isEmpty()) {
            $this->error('No families found.');

            return 1;
        }

        $maxWidth = (int) $this->option('width');
        $quality = (int) $this->option('quality');
        $force = (bool) $this->option('force');

        $total = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($families as $family) {
            $this->info("\nProcessing: {$family->name} ({$family->directory_name})");

            // Collect photos from both locations
            $photos = array_merge(
                array_map(fn ($f) => ['file' => $f, 'location' => 'uploads'],
                    $this->photoService->getFamilyPhotos($family)),
                array_map(fn ($f) => ['file' => $f, 'location' => 'final_choices'],
                    $this->photoService->getFinalChoicesPhotos($family)),
            );

            if (empty($photos)) {
                $this->line('  No photos found, skipping.');

                continue;
            }

            $bar = $this->output->createProgressBar(count($photos));
            $bar->start();

            foreach ($photos as ['file' => $filename, 'location' => $location]) {
                if (! $force && $this->photoService->thumbnailExists($family->directory_name, $filename)) {
                    $skipped++;
                    $bar->advance();

                    continue;
                }

                try {
                    $imageData = $this->fetchImageData($driver, $location, $family->directory_name, $filename);

                    if ($imageData === null) {
                        $this->newLine();
                        $this->warn("  Could not fetch: {$filename}");
                        $failed++;
                        $bar->advance();

                        continue;
                    }

                    $thumb = $this->resize($imageData, $maxWidth, $quality);
                    $this->photoService->storeThumbnail($family->directory_name, $filename, $thumb);
                    $total++;
                } catch (\Throwable $e) {
                    $this->newLine();
                    $this->warn("  Failed {$filename}: {$e->getMessage()}");
                    $failed++;
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        $this->info("\nDone. Generated: {$total}  |  Skipped (already exist): {$skipped}  |  Failed: {$failed}");

        return $failed > 0 ? 1 : 0;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function fetchImageData(string $driver, string $location, string $family, string $filename): ?string
    {
        if ($driver === 'r2') {
            $prefix = $location === 'final_choices' ? 'final_choices' : 'uploads';

            return Storage::disk('r2')->get("{$prefix}/{$family}/{$filename}") ?: null;
        }

        // Local filesystem
        $base = $location === 'final_choices'
            ? storage_path("app/photos/final_choices/{$family}/{$filename}")
            : storage_path("app/photos/uploads/{$family}/{$filename}");

        return file_exists($base) ? file_get_contents($base) : null;
    }

    private function resize(string $imageData, int $maxWidth, int $quality): string
    {
        $src = @imagecreatefromstring($imageData);
        if ($src === false) {
            throw new \RuntimeException('Could not decode image — unsupported format or corrupt file');
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        if ($origW <= $maxWidth) {
            // Already within size limit — re-encode at reduced quality only
            ob_start();
            imagejpeg($src, null, $quality);
            $out = ob_get_clean();
            imagedestroy($src);

            return $out;
        }

        $ratio = $maxWidth / $origW;
        $newW = $maxWidth;
        $newH = (int) round($origH * $ratio);

        $dst = imagecreatetruecolor($newW, $newH);

        // Preserve alpha channel for PNG sources
        imagealphablending($dst, false);
        imagesavealpha($dst, true);

        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        ob_start();
        imagejpeg($dst, null, $quality);
        $out = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $out;
    }
}
