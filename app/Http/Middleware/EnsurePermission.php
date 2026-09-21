<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permission enforcement middleware.
 *
 * Registered as the `permission` alias in bootstrap/app.php and used as:
 *
 *   Route::middleware('permission:users.view')->group(...);
 *   Route::get(...)->middleware('permission:users.update');
 *
 * Multiple permissions may be required (ALL must be held):
 *
 *   ->middleware('permission:roles.view,roles.update')
 *
 * Deny-by-default: an unauthenticated user is redirected to login, an
 * authenticated user without the permission gets 403.
 *
 * IMPORTANT: this is the *authorization* gate. Hiding a sidebar item is purely
 * cosmetic and never a substitute — every protected route carries this
 * middleware. See docs/PERMISSIONS.md.
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->guest(route('login'));
        }

        // Deactivated users must not use the application at all.
        if (! $user->isActive()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'Your account has been deactivated.');
        }

        foreach ($permissions as $permission) {
            if (! $user->hasPermission($permission)) {
                abort(403, "You do not have permission to access this area ({$permission}).");
            }
        }

        return $next($request);
    }
}
