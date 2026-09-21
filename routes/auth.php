<?php
/*
|--------------------------------------------------------------------------
| Authentication Routes — Fish Farm ERP
|--------------------------------------------------------------------------
|
| Laravel's BUILT-IN session authentication. No external auth package
| (no Breeze / Jetstream / Fortify) — see docs/PROJECT.md §3.
|
| Flow:
|   GET  /login   → show the form           (guest only)
|   POST /login   → authenticate + throttle (guest only)
|   POST /logout  → destroy the session     (authenticated only)
|
| Security:
|   - POST routes are CSRF-protected by the `web` middleware group.
|   - Attempts are rate-limited in LoginController (5/min per email+IP).
|   - The session id is regenerated on login (session fixation protection).
|   - Inactive accounts are refused even with valid credentials.
|
| See docs/PERMISSIONS.md for authorization and docs/PROJECT.md §8.
*/

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
});
