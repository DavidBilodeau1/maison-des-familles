<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Services\PhotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FamilyController extends Controller
{
    protected $photoService;

    public function __construct(PhotoService $photoService)
    {
        $this->photoService = $photoService;
    }

    public function index()
    {
        $families = Family::withCount('selectedPhotos')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.families.index', compact('families'));
    }

    public function create()
    {
        return view('admin.families.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'directory_name' => 'required|string|max:255|unique:families,directory_name',
        ]);

        $family = Family::create([
            'name' => $request->name,
            'directory_name' => $request->directory_name,
            'pin' => $this->generatePin(),
            'login_enabled' => false,
            'selection_completed' => false,
        ]);

        $path = $this->photoService->createFamilyDirectory($family->directory_name);

        $message = 'Family created successfully. PIN: '.$family->pin;
        $message .= match ($this->photoService->storageDriver()) {
            'r2' => ' — Upload photos to your R2 bucket under: '.$path.'/',
            'webhook' => ' — Create this folder on your local machine: '.$path,
            default => '',
        };

        return redirect()->route('admin.families.show', $family)
            ->with('success', $message);
    }

    public function show(Family $family)
    {
        if (! $family->selection_completed) {
            $this->photoService->syncFamilyPhotos($family);
        }

        $family->load('photoSelections');

        $photoUrls = $family->photoSelections->mapWithKeys(fn ($photo) => [
            $photo->id => $this->photoService->getPhotoUrl($family->directory_name, $photo->photo_filename,
                $photo->is_selected ? 'final_choices' : 'uploads'),
        ]);

        $photoFallbackUrls = $family->photoSelections->mapWithKeys(fn ($photo) => [
            $photo->id => $photo->is_selected
                ? $this->photoService->getPhotoUrl($family->directory_name, $photo->photo_filename, 'uploads')
                : null,
        ]);

        return view('admin.families.show', compact('family', 'photoUrls', 'photoFallbackUrls'));
    }

    public function edit(Family $family)
    {
        return view('admin.families.edit', compact('family'));
    }

    public function update(Request $request, Family $family)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'login_enabled' => 'boolean',
        ]);

        $family->update([
            'name' => $request->name,
            'login_enabled' => $request->has('login_enabled'),
        ]);

        return redirect()->route('admin.families.show', $family)
            ->with('success', 'Family updated successfully.');
    }

    public function destroy(Family $family)
    {
        $family->delete();

        return redirect()->route('admin.families.index')
            ->with('success', 'Family deleted successfully.');
    }

    public function toggleLogin(Family $family)
    {
        $family->login_enabled = ! $family->login_enabled;
        $family->save();

        return back()->with('success', 'Login access '.($family->login_enabled ? 'enabled' : 'disabled'));
    }

    public function enableAllLogins()
    {
        $count = Family::where('login_enabled', false)->count();
        Family::query()->update(['login_enabled' => true]);

        return redirect()->route('admin.families.index')
            ->with('success', "Connexion activée pour {$count} famille(s).");
    }

    public function createAllDirectories()
    {
        $families = Family::all();
        $created = 0;

        foreach ($families as $family) {
            $this->photoService->createFamilyDirectory($family->directory_name);
            $created++;
        }

        return redirect()->route('admin.families.index')
            ->with('success', "Dossiers créés ou vérifiés pour {$created} familles.");
    }

    public function resetSession(Family $family)
    {
        $family->session_started_at = null;
        $family->session_expires_at = null;
        $family->selection_completed = false;
        $family->save();

        return back()->with('success', 'Session reset successfully.');
    }

    public function selectionInfo(Family $family)
    {
        $photos = $family->selectedPhotos;

        return response()->json([
            'name' => $family->name,
            'photos' => $photos->map(fn ($p) => [
                'filename' => $p->photo_filename,
                'url' => $this->photoService->getPhotoUrl(
                    $family->directory_name, $p->photo_filename, 'final_choices'
                ),
                'fallback_url' => $this->photoService->getPhotoUrl(
                    $family->directory_name, $p->photo_filename, 'uploads'
                ),
            ]),
            'download_url' => route('admin.families.download-selection', $family),
        ]);
    }

    public function downloadSelection(Family $family)
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $photos = $family->selectedPhotos;

        if ($photos->isEmpty()) {
            return redirect()->route('admin.families.index')
                ->with('error', 'Aucune photo sélectionnée pour cette famille.');
        }

        $zipName = $family->directory_name.'_selection.zip';
        $tmpPath = tempnam(sys_get_temp_dir(), 'selection_');

        $zip = new ZipArchive;
        if ($zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Could not create ZIP file.');
        }

        foreach ($photos as $photo) {
            $finalKey = "final_choices/{$family->directory_name}/{$photo->photo_filename}";
            $uploadsKey = "uploads/{$family->directory_name}/{$photo->photo_filename}";

            $data = Storage::disk('r2')->get($finalKey)
                 ?? Storage::disk('r2')->get($uploadsKey);

            if ($data !== null) {
                $zip->addFromString($photo->photo_filename, $data);
            }
        }

        $zip->close();

        return response()->download($tmpPath, $zipName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    protected function generatePin()
    {
        do {
            $pin = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Family::where('pin', $pin)->exists());

        return $pin;
    }
}
