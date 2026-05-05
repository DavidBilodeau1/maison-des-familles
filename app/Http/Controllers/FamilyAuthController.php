<?php

namespace App\Http\Controllers;

use App\Models\Family;
use Illuminate\Http\Request;

class FamilyAuthController extends Controller
{
    public function showLoginForm()
    {
        return view('family.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|size:8',
        ]);

        $family = Family::where('pin', $request->pin)
            ->where('login_enabled', true)
            ->first();

        if (! $family) {
            return back()->withErrors([
                'pin' => 'PIN invalide ou connexion non activée pour cette famille.',
            ]);
        }

        // Check if selection is already completed
        if ($family->selection_completed) {
            return back()->withErrors([
                'pin' => 'Votre sélection de photos a déjà été complétée. Veuillez contacter l\'administrateur si vous devez apporter des modifications.',
            ]);
        }

        // Start session if not already started or if session expired
        if (! $family->session_started_at || ! $family->isSessionActive()) {
            $family->startSession();
        }

        // Store family ID in session
        session(['family_id' => $family->id]);

        return redirect()->route('family.photos');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('family_id');

        return redirect()->route('family.login');
    }
}
