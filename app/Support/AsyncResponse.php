<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * AsyncResponse — one place that decides "redirect or JSON?".
 *
 * The ERP is server-rendered Blade (docs/ARCHITECTURE.md). Most actions redirect
 * with a flash message. But when the request came from the async layer
 * (resources/js/components/async-actions.js — it sends X-Requested-With +
 * Accept: application/json), a redirect is wrong: the browser would reload.
 *
 * A controller therefore ends an action with ONE of these:
 *
 *   return AsyncResponse::ok($request, 'Pond deleted.', 'ponds.index');
 *
 *   - normal request  → redirect (unchanged behaviour, flash message, no regression)
 *   - async request   → 200 JSON { message, tone } and the DOM updates in place
 *
 * This keeps every existing route working exactly as before for non-JS clients
 * while giving the async layer what it needs. It contains NO business logic.
 */
final class AsyncResponse
{
    /** True when the client wants JSON rather than a full page. */
    public static function wants(Request $request): bool
    {
        // Inertia sends X-Requested-With (so `ajax()` is true) but it does NOT want
        // raw JSON — it needs the normal redirect so it can perform an Inertia visit.
        // Treating an Inertia request as "async" makes the page silently stay put.
        if ($request->header('X-Inertia')) {
            return false;
        }

        return $request->expectsJson() || $request->ajax();
    }

    /**
     * Success: redirect for a normal request, JSON for an async one.
     *
     * @param  string|null  $route  route name to redirect to (normal requests)
     * @param  array<string, mixed>  $params  route parameters
     */
    public static function ok(
        Request $request,
        string $message,
        ?string $route = null,
        array $params = [],
        string $tone = 'success',
        bool $reload = false,
    ): JsonResponse|RedirectResponse {
        if (self::wants($request)) {
            return response()->json([
                'message' => $message,
                'tone' => $tone,
                'reload' => $reload,
            ]);
        }

        $redirect = $route ? redirect()->route($route, $params) : back();

        return $redirect->with($tone, $message);
    }

    /**
     * Business-rule refusal (e.g. "a pond holding live fish cannot be deleted").
     *
     * Returns 422 for async requests so the async layer shows the message as an
     * error toast — NOT a 500, and never a stack trace (docs/ERP-UI-UX.md
     * "Error states").
     */
    public static function refuse(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if (self::wants($request)) {
            return response()->json([
                'message' => $message,
                'tone' => 'error',
            ], 422);
        }

        return back()->with('error', $message);
    }
}
