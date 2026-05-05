<?php

namespace App\Services;

use App\Models\Family;
use App\Models\PhotoSelection;
use Illuminate\Support\Facades\File;

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
        $familyDir = $this->uploadsPath.'/'.$family->directory_name;

        if (! File::exists($familyDir)) {
            return [];
        }

        $photos = [];
        $files = File::files($familyDir);

        foreach ($files as $file) {
            if ($this->isImageFile($file)) {
                $photos[] = $file->getFilename();
            }
        }

        return $photos;
    }

    public function syncFamilyPhotos(Family $family)
    {
        // Get photos from uploads directory
        $uploadsPhotos = $this->getFamilyPhotos($family);

        // Get photos from final_choices directory
        $finalPhotos = $this->getFinalChoicesPhotos($family);

        // Get existing photo selections
        $existingPhotos = $family->photoSelections()->pluck('photo_filename')->toArray();

        // Add new photos from uploads
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

        // Add new photos from final_choices (shouldn't happen normally, but just in case)
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

        // Remove photos that no longer exist in uploads directory
        // Only delete photos that are still in 'uploads' location
        $family->photoSelections()
            ->where('location', 'uploads')
            ->whereNotIn('photo_filename', $uploadsPhotos)
            ->delete();

        // Remove photos that no longer exist in final_choices directory
        $family->photoSelections()
            ->where('location', 'final_choices')
            ->whereNotIn('photo_filename', $finalPhotos)
            ->delete();
    }

    public function getFinalChoicesPhotos(Family $family)
    {
        $familyDir = $this->finalChoicesPath.'/'.$family->directory_name;

        if (! File::exists($familyDir)) {
            return [];
        }

        $photos = [];
        $files = File::files($familyDir);

        foreach ($files as $file) {
            if ($this->isImageFile($file)) {
                $photos[] = $file->getFilename();
            }
        }

        return $photos;
    }

    public function moveSelectedPhotos(Family $family)
    {
        $selectedPhotos = $family->selectedPhotos;

        if ($selectedPhotos->isEmpty()) {
            return;
        }

        $sourceDir = $this->uploadsPath.'/'.$family->directory_name;
        $destDir = $this->finalChoicesPath.'/'.$family->directory_name;

        // Create destination directory if it doesn't exist
        if (! File::exists($destDir)) {
            File::makeDirectory($destDir, 0755, true);
        }

        foreach ($selectedPhotos as $selection) {
            $sourcePath = $sourceDir.'/'.$selection->photo_filename;
            $destPath = $destDir.'/'.$selection->photo_filename;

            if (File::exists($sourcePath)) {
                File::move($sourcePath, $destPath);

                // Update the location in the database
                $selection->update(['location' => 'final_choices']);
            }
        }
    }

    public function getPhotoUrl($familyDirectoryName, $filename)
    {
        return '/photos/'.$familyDirectoryName.'/'.$filename;
    }

    protected function isImageFile($file)
    {
        $extension = strtolower($file->getExtension());

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    public function createFamilyDirectory($directoryName)
    {
        $familyDir = $this->uploadsPath.'/'.$directoryName;

        if (! File::exists($familyDir)) {
            File::makeDirectory($familyDir, 0755, true);
        }

        return $familyDir;
    }
}
