<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// Password reset landing page (linked from email). Renders a minimal form that posts to the API reset endpoint.
Route::get('/password/reset/{token}', function (Request $request, string $token) {
    return view('password-reset', [
        'token' => $token,
        'email' => $request->query('email', ''),
    ]);
})->name('password.reset');

// Named login route for auth middleware redirects; served by the SPA shell.
Route::get('/login', ApplicationController::class)->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:web')->name('logout');
Route::get('/csrf-cookie', fn () => response()->noContent());

Route::get('/privacy', function () {
    $path = public_path('PRIVACY.md');
    abort_unless(File::exists($path), 404);
    $content = File::get($path);
    return view('legal', [
        'title' => 'Privacy',
        'content' => Str::markdown($content),
    ]);
});

Route::get('/terms', function () {
    $path = public_path('TERMS.md');
    abort_unless(File::exists($path), 404);
    $content = File::get($path);
    return view('legal', [
        'title' => 'Terms of Use',
        'content' => Str::markdown($content),
    ]);
});

// Explicitly return 404 for legacy paths from the old multi-SPA setup.
Route::any('/gratserver/{any?}', fn () => abort(404))->where('any', '.*');
Route::any('/gratclient/{any?}', fn () => abort(404))->where('any', '.*');

// Single SPA (admin + student client) at root
Route::get('/{any?}', ApplicationController::class)->where('any', '.*');
