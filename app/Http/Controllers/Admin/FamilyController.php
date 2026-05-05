<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Services\PhotoService;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    protected $photoService;

    public function __construct(PhotoService $photoService)
    {
        $this->photoService = $photoService;
    }

    public function index()
    {
        $families = Family::orderBy('created_at', 'desc')->get();

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

        // Create directory for family photos
        $this->photoService->createFamilyDirectory($family->directory_name);

        return redirect()->route('admin.families.show', $family)
            ->with('success', 'Family created successfully. PIN: '.$family->pin);
    }

    public function show(Family $family)
    {
        // Sync photos from filesystem
        $this->photoService->syncFamilyPhotos($family);

        $family->load('photoSelections');

        return view('admin.families.show', compact('family'));
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

    public function resetSession(Family $family)
    {
        $family->session_started_at = null;
        $family->session_expires_at = null;
        $family->selection_completed = false;
        $family->save();

        return back()->with('success', 'Session reset successfully.');
    }

    protected function generatePin()
    {
        do {
            $pin = str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Family::where('pin', $pin)->exists());

        return $pin;
    }
}
