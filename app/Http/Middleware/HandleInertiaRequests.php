<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * HandleInertiaRequests — the Inertia root-view + shared-props contract.
 *
 * Shared props are deliberately SMALL (docs/ERP-INERTIA-MIGRATION.md §"Inertia props"):
 * only what every page needs — the authenticated user (public fields), flash messages
 * and the company branding used by the shell. Never dump whole tables here.
 *
 * The existing session auth is reused as-is; nothing about authentication changes.
 */
class HandleInertiaRequests extends Middleware
{
    /** The root Blade template rendered on the first (non-Inertia) page load. */
    protected $rootView = 'app';

    /**
     * Shared props — kept minimal on purpose.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                // Only public identity fields — never the whole user row.
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_super_admin' => $user->hasRole('super_admin'),
                    // Permission names for sidebar + button visibility (UX ONLY —
                    // Laravel middleware/policies remain the real enforcement).
                    'permissions' => $user->permissionNames()->all(),
                ] : null,
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'warning' => fn() => $request->session()->get('warning'),
                'info' => fn() => $request->session()->get('info'),
            ],
            'company' => fn() => [
                'name' => config('fishfarm.company.name') ?: config('app.name'),
                'initials' => $this->initials(config('fishfarm.company.name') ?: config('app.name')),
                // Same single source as the `currency` prop below — never a second read.
                'currency' => \App\Support\Currency::code(),
            ],
            // The ONE currency definition the whole UI formats with (symbol + locale).
            'currency' => fn() => \App\Support\Currency::shared(),
            // Header notification centre — lightweight: an unread COUNT + a small
            // recent slice. The full list is fetched on demand, never sent globally.
            'notifications' => fn() => $user ? [
                'unread' => app(\App\Services\Notification\NotificationService::class)->unreadCount($user->id),
                'recent' => app(\App\Services\Notification\NotificationService::class)->recent($user->id, 6),
            ] : ['unread' => 0, 'recent' => []],
            // Server clock for the header calendar (application timezone).
            'now' => fn() => [
                'date' => now()->toDateString(),
                'timezone' => config('app.timezone'),
            ],
            'app' => [
                'name' => config('app.name'),
                'version' => config('fishfarm.version', 'v0'),
            ],
            // Route NAME → URL for the navigation menu. Only the names the sidebar
            // needs are resolved (never the whole route table). The frontend resolves
            // links from this map, so URLs are never hard-coded in React.
            'routes' => fn () => $this->navigationRoutes(),
        ]);
    }

    /**
     * Resolve the URL of every route referenced by config/navigation.php.
     * Missing routes are simply omitted, so the sidebar can render them disabled
     * (an honest "not available" state) instead of a broken link.
     *
     * @return array<string, string>
     */
    private function navigationRoutes(): array
    {
        $names = [];
        foreach (config('navigation', []) as $entry) {
            if (! empty($entry['route'])) {
                $names[] = $entry['route'];
            }
            foreach ($entry['children'] ?? [] as $child) {
                if (! empty($child['route'])) {
                    $names[] = $child['route'];
                }
            }
        }

        $map = [];
        foreach (array_unique($names) as $name) {
            if (\Illuminate\Support\Facades\Route::has($name)) {
                // A route with required parameters (e.g. reports.export/{report})
                // cannot be URL-generated without them — send a template instead.
                if ($this->routeNeedsParams($name)) {
                    $map[$name] = '/' . ltrim(\Illuminate\Support\Facades\Route::getRoutes()->getByName($name)->uri(), '/');
                    continue;
                }
                $map[$name] = route($name, absolute: false);
            }
        }

        // Report CSV export: a template the frontend fills with the report key
        // (e.g. /fish-farm/reports/{report}/export). It is NOT an Inertia visit —
        // it streams a file — so the frontend only builds a plain href from it.
        if (\Illuminate\Support\Facades\Route::has('reports.export')) {
            $map['reports.export'] = '/' . ltrim(\Illuminate\Support\Facades\Route::getRoutes()->getByName('reports.export')->uri(), '/');
        }

        // Extra routes the migrated React pages need but that are NOT part of the
        // sidebar menu (list exports, settings entry points, …). Adding one here is
        // the ONLY place needed — the frontend still never hard-codes a URL.
        foreach (self::EXTRA_ROUTES as $name) {
            if (\Illuminate\Support\Facades\Route::has($name) && ! isset($map[$name])) {
                $map[$name] = $this->routeNeedsParams($name)
                    ? '/' . ltrim(\Illuminate\Support\Facades\Route::getRoutes()->getByName($name)->uri(), '/')
                    : route($name, absolute: false);
            }
        }

        return $map;
    }

    /**
     * Route names the React pages rely on that are not in config/navigation.php.
     * Kept explicit and small on purpose (docs/ERP-UI-UX.md).
     *
     * @var list<string>
     */
    private const EXTRA_ROUTES = [
        // Pond Management
        'ponds.show',
        'ponds.store',
        'ponds.update',
        'ponds.destroy',
        'ponds.export',
        'ponds.types.store',
        'ponds.types.update',
        'ponds.types.destroy',
        // Fish Stock
        'fish.species.store',
        'fish.species.update',
        'fish.species.destroy',
        'fish.stockings.create',
        'fish.stockings.store',
        'fish.stockings.destroy',
        'fish.mortalities.create',
        'fish.mortalities.store',
        'fish.mortalities.destroy',
        'fish.harvests.create',
        'fish.harvests.store',
        'fish.harvests.destroy',
        'fish.stockings.export',
        'fish.mortalities.export',
        'fish.harvests.export',
        // Fish batches / cycles
        'fish.batches.index',
        'fish.batches.create',
        'fish.batches.store',
        'fish.batches.show',
        'fish.batches.edit',
        'fish.batches.update',
        'fish.batches.destroy',
        // Pond Ledger
        'ledger.stocking',
        'ledger.stocking.store',
        'ledger.mortality',
        'ledger.mortality.store',
        'ledger.transfers',
        'ledger.transfers.store',
        'ledger.transfers.destroy',
        'ledger.transactions.store',
        'ledger.transactions.destroy',
        // Feed
        'feed.types.store',
        'feed.types.update',
        'feed.types.destroy',
        'feed.purchases.create',
        'feed.purchases.store',
        'feed.purchases.destroy',
        'feed.usages.create',
        'feed.usages.store',
        'feed.usages.destroy',
        'feed.adjustments.create',
        'feed.adjustments.store',
        'feed.adjustments.destroy',
        // Sales
        'sales.list',
        'sales.export',
        'sales.create',
        'sales.show',
        'sales.edit',
        'sales.store',
        'sales.update',
        'sales.destroy',
        // Customers
        'customers.create',
        'customers.store',
        'customers.update',
        'customers.destroy',
        'customers.export',
        'customers.dues',
        'customers.dues.export',
        'customers.payments.create',
        'customers.payments.store',
        'customers.payments.destroy',
        'customers.payments.export',
        // Suppliers
        'suppliers.store',
        'suppliers.create',
        'suppliers.update',
        'suppliers.destroy',
        'suppliers.export',
        'suppliers.dues.export',
        'suppliers.purchases.create',
        'suppliers.purchases.store',
        'suppliers.purchases.edit',
        'suppliers.purchases.update',
        'suppliers.purchases.destroy',
        'suppliers.purchases.export',
        'suppliers.payments.create',
        'suppliers.payments.store',
        'suppliers.payments.destroy',
        'suppliers.payments.export',
        // Party
        'parties.create',
        'parties.store',
        'parties.update',
        'parties.destroy',
        'parties.export',
        'parties.ledger.export',
        'parties.transactions.create',
        'parties.transactions.store',
        'parties.transactions.destroy',
        'parties.transactions.export',
        // Finance
        'finance.income.index',
        'finance.income.create',
        'finance.income.store',
        'finance.income.destroy',
        'finance.income.export',
        'finance.expenses.index',
        'finance.expenses.create',
        'finance.expenses.store',
        'finance.expenses.destroy',
        'finance.expenses.export',
        'finance.categories.index',
        'finance.categories.create',
        'finance.categories.store',
        'finance.categories.edit',
        'finance.categories.update',
        'finance.categories.destroy',
        'finance.profit-loss',
        'finance.profit-loss.export',
        // FCR & Growth
        'fcr.growth',
        'fcr.growth.create',
        'fcr.growth.store',
        'fcr.growth.edit',
        'fcr.growth.update',
        'fcr.growth.destroy',
        'fcr.inspections.index',
        'fcr.inspections.create',
        'fcr.inspections.store',
        'fcr.inspections.show',
        'fcr.inspections.edit',
        'fcr.inspections.update',
        'fcr.inspections.destroy',
        'fcr.inspections.export',
        'fcr.feed-growth',
        'fcr.comparison',
        'fcr.schedules.index',
        'fcr.schedules.create',
        'fcr.schedules.store',
        'fcr.schedules.edit',
        'fcr.schedules.update',
        'fcr.schedules.destroy',
        'fcr.reports',
        'fcr.export',
        // Settings
        'settings.company.update',
        'settings.profile.update',
        'settings.users.create',
        'settings.users.store',
        'settings.users.edit',
        'settings.users.update',
        'settings.users.toggle-active',
        'settings.users.destroy',
        'settings.roles.create',
        'settings.roles.store',
        'settings.roles.edit',
        'settings.roles.update',
        'settings.roles.destroy',
        // Feed
        'feed.purchases.export',
        'feed.usages.export',
        // Feeding & meal schedules
        'feed.feedings.index',
        'feed.feedings.create',
        'feed.feedings.store',
        'feed.feedings.destroy',
        'feed.schedules.index',
        'feed.schedules.create',
        'feed.schedules.store',
        'feed.schedules.edit',
        'feed.schedules.update',
        'feed.schedules.destroy',
    ];

    /** Does this route require parameters that make a bare route() call fail? */
    private function routeNeedsParams(string $name): bool
    {
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName($name);

        return $route !== null && count($route->parameterNames()) > 0;
    }

    /** Two-letter brand initials, never hard-coded. */
    private function initials(string $name): string
    {
        $words = preg_split('/\s+/', trim($name)) ?: [];
        $letters = array_map(fn($w) => mb_substr($w, 0, 1), array_slice($words, 0, 2));

        return mb_strtoupper(implode('', $letters)) ?: 'FF';
    }
}
