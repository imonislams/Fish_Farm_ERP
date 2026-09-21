<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Session authentication (Laravel's built-in guard — no external package).
 *
 * Responsibilities (thin controller — see docs/ARCHITECTURE.md):
 *   - show the login form
 *   - validate credentials, throttle attempts, verify the account is active
 *   - regenerate the session on success and redirect to the intended page
 *   - logout: invalidate session and regenerate CSRF token
 *
 * No business logic lives here.
 */
class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 60;

    /** Show the login form. */
    public function create(): View
    {
        return view('auth.login', [
            'title' => 'Sign in',
        ]);
    }

    /** Handle a login attempt. */
    public function store(LoginRequest $request): RedirectResponse
    {
        $this->ensureIsNotRateLimited($request);

        $credentials = [
            'email' => $request->string('email')->lower()->trim()->toString(),
            'password' => $request->string('password')->toString(),
        ];

        if (! Auth::attempt($credentials, $request->remember())) {
            RateLimiter::hit($this->throttleKey($request), self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        // --- Authenticated. Now enforce the account state. ---------------
        /** @var User $user */
        $user = Auth::user();

        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'This account has been deactivated. Please contact an administrator.',
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        // Prevent session fixation.
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Welcome back, ' . $user->name . '.');
    }

    /** Log the user out and destroy the session. */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been signed out.');
    }

    /**
     * Throttle failed logins (5 per minute per email+IP).
     * Brute-force protection — see docs/PERMISSIONS.md / PROJECT.md §12.
     */
    private function ensureIsNotRateLimited(LoginRequest $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
        ]);
    }

    private function throttleKey(LoginRequest $request): string
    {
        $email = Str::lower((string) $request->input('email'));

        return Str::transliterate($email) . '|' . $request->ip();
    }
}
