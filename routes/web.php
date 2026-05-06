<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\FamilyController as AdminFamilyController;
use App\Http\Controllers\FamilyAuthController;
use App\Http\Controllers\FamilyPhotoController;
use Illuminate\Support\Facades\Route;

// Redirect root to family login
Route::get('/', function () {
    return redirect()->route('family.login');
});

// Family routes
Route::prefix('family')->name('family.')->group(function () {
    Route::get('login', [FamilyAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [FamilyAuthController::class, 'login'])->name('login.submit');
    Route::post('logout', [FamilyAuthController::class, 'logout'])->name('logout');

    Route::middleware('family.auth')->group(function () {
        Route::get('photos', [FamilyPhotoController::class, 'index'])->name('photos');
        Route::post('photos/toggle', [FamilyPhotoController::class, 'toggleSelection'])->name('photos.toggle');
        Route::post('photos/submit', [FamilyPhotoController::class, 'submit'])->name('photos.submit');
    });
});

// Admin routes
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware('admin.auth')->group(function () {
        Route::resource('families', AdminFamilyController::class);
        Route::post('families/{family}/toggle-login', [AdminFamilyController::class, 'toggleLogin'])->name('families.toggle-login');
        Route::post('families/{family}/reset-session', [AdminFamilyController::class, 'resetSession'])->name('families.reset-session');
    });
});

// Serve photos from storage
Route::get('/photos/{family}/{filename}', function ($family, $filename) {
    $path = storage_path('app/photos/uploads/'.$family.'/'.$filename);

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->name('photos.serve');

Route::get('/final/{family}/{filename}', function ($family, $filename) {
    $path = storage_path('app/photos/final_choices/'.$family.'/'.$filename);

    if (! file_exists($path)) {
        abort(404);
    }

    return response()->file($path);
})->name('photos.serve.final');
