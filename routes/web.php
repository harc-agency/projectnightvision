<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\DreamController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\LocationPredictionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SymbolController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dream-globe', [ExploreController::class, 'globe'])->name('globe');
Route::get('/library', [ExploreController::class, 'library'])->name('library');

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('dreams/{dream}/media/{kind}', [DreamController::class, 'media'])->name('dreams.media');
    Route::patch('dreams/{dream}/visibility', [DreamController::class, 'updateVisibility'])->name('dreams.visibility');
    Route::post('dreams/{dream}/generate-assets', [DreamController::class, 'generateAssets'])->name('dreams.generate-assets');
    Route::get('locations/predict', [LocationPredictionController::class, 'index'])
        ->middleware('throttle:30,1')
        ->name('locations.predict');
    Route::resource('dreams', DreamController::class);
    Route::post('dreams/transcribe', [DreamController::class, 'transcribe'])->name('dreams.transcribe');
    Route::resource('symbols', SymbolController::class);
    Route::get('symbols/{symbol}', [SymbolController::class, 'show'])->name('symbols.show');
    Route::get('/submit-dream', fn () => redirect()->route('dreams.create'))->name('submit-dream');
    Route::resource('profile', ProfileController::class)->only(['edit', 'update', 'destroy']);
});

Route::get('auth/google', [GoogleController::class, 'redirectToGoogle']);
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

require __DIR__.'/auth.php';
