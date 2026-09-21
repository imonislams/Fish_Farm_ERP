<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

/**
 * Renders the honest "module not implemented yet" placeholder.
 *
 * This exists so route scaffolding can be complete without inventing any
 * business behaviour. Every placeholder route is replaced by its real
 * controller as that module is implemented - see docs/MODULES.md for status.
 *
 * The human-readable title is derived from config/navigation.php so headings
 * stay consistent with the sidebar and never drift.
 */
class PendingController extends Controller
{
    public function __invoke(): View
    {
        $routeName = (string) request()->route()?->getName();

        return view('placeholders.module', [
            'title' => $this->titleFor($routeName),
            'routeName' => $routeName,
        ]);
    }

    /**
     * Look the current route name up in the navigation config to get its label.
     * Falls back to a title-cased version of the route name.
     */
    private function titleFor(string $routeName): string
    {
        foreach (config('navigation', []) as $entry) {
            if (($entry['route'] ?? null) === $routeName) {
                return $entry['label'];
            }

            foreach ($entry['children'] ?? [] as $child) {
                if (($child['route'] ?? null) === $routeName) {
                    return $child['label'];
                }
            }
        }

        $tail = Str::afterLast($routeName, '.');
        $fallback = trim(ucwords(str_replace('-', ' ', $tail)));

        return $fallback !== '' ? $fallback : 'Module';
    }
}