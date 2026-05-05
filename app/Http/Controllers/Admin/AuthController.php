<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        // Simple password check - in production, use proper authentication
        if ($request->password === env('ADMIN_PASSWORD', 'admin123')) {
            session(['admin_logged_in' => true]);

            return redirect()->route('admin.families.index');
        }

        return back()->withErrors([
            'password' => 'Invalid password.',
        ]);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_logged_in');

        return redirect()->route('admin.login');
    }
}
