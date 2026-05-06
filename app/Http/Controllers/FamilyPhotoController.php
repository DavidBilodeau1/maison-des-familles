<?php

namespace App\Http\Controllers;

use App\Models\Family;
use App\Services\PhotoService;
use Illuminate\Http\Request;

class FamilyPhotoController extends Controller
{
    protected $photoService;

    public function __construct(PhotoService $photoService)
    {
        $this->photoService = $photoService;
    }

    public function index(Request $request)
    {
        $familyId = session('family_id');

        if (! $familyId) {
            return redirect()->route('family.login');
        }

        $family = Family::findOrFail($familyId);

        // Check if session is still active
        if (! $family->isSessionActive()) {
            // Complete selection and move photos
            $this->completeSelection($family);

            session()->forget('family_id');

            return redirect()->route('family.login')
                ->with('message', 'Votre session a expiré. Vos sélections ont été sauvegardées.');
        }

        // Sync photos from filesystem
        $this->photoService->syncFamilyPhotos($family);

        $photos = $family->photoSelections;
        $timeRemaining = max(0, now()->diffInSeconds($family->session_expires_at, false));

        $photoUrls = $photos->mapWithKeys(fn ($photo) => [
            $photo->id => $this->photoService->getPhotoUrl($family->directory_name, $photo->photo_filename, $photo->location),
        ]);

        return view('family.photos', compact('family', 'photos', 'timeRemaining', 'photoUrls'));
    }

    public function toggleSelection(Request $request)
    {
        $familyId = session('family_id');

        if (! $familyId) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $family = Family::findOrFail($familyId);

        if (! $family->isSessionActive()) {
            return response()->json(['error' => 'Session expired'], 403);
        }

        $request->validate([
            'photo_id' => 'required|exists:photo_selections,id',
        ]);

        $photo = $family->photoSelections()->findOrFail($request->photo_id);
        $photo->is_selected = ! $photo->is_selected;
        $photo->save();

        return response()->json([
            'success' => true,
            'is_selected' => $photo->is_selected,
        ]);
    }

    public function submit(Request $request)
    {
        $familyId = session('family_id');

        if (! $familyId) {
            return redirect()->route('family.login');
        }

        $family = Family::findOrFail($familyId);

        $this->completeSelection($family);

        session()->forget('family_id');

        return redirect()->route('family.login')
            ->with('message', 'Thank you! Your photo selections have been saved.');
    }

    protected function completeSelection(Family $family)
    {
        if (! $family->selection_completed) {
            $this->photoService->moveSelectedPhotos($family);
            $family->selection_completed = true;
            $family->save();
        }
    }
}
