<?php

namespace App\Services\Dashboard;

use App\Support\Metric;
use Illuminate\Support\Collection;

/**
 * Dashboard metrics.
 *
 * ARCHITECTURAL RULE (docs/ARCHITECTURE.md + docs/MODULES.md):
 * the dashboard must show REAL database values. Until a module's query exists,
 * its metric is reported as `Metric::pending()` and renders as "—" with an
 * honest note — never a hard-coded or invented number.
 *
 * As each module lands, replace the corresponding `Metric::pending(...)` call
 * with a real aggregate query (indexed columns, eager loading, no N+1).
 */
final class DashboardMetricsService
{
    /**
     * The required KPI set from the specification.
     *
     * @return Collection<int, Metric>
     */
    public function kpis(): Collection
    {
        return collect([
            Metric::pending("Today's Sales"),
            Metric::pending("Today's Feed"),
            Metric::pending("Today's Income"),
            Metric::pending("Today's Collection"),
            Metric::pending('Total Due'),
            Metric::pending('Cash Position'),
            Metric::pending('Average FCR'),
            Metric::pending("Today's Inspection"),
            Metric::pending('Inspection Due'),
            Metric::pending('Best Pond'),
        ]);
    }

    /**
     * Analytics blocks. Each returns a placeholder empty collection until its
     * module query is implemented, so charts render an empty state rather than
     * fabricated data.
     *
     * @return array<string, Collection>
     */
    public function analytics(): array
    {
        return [
            'pond_status' => collect(),
            'fish_stock' => collect(),
            'feed_usage' => collect(),
            'growth' => collect(),
            'sales' => collect(),
            'income_vs_expense' => collect(),
        ];
    }

    /** Recent transactions (sales, payments, purchases). */
    public function recentTransactions(): Collection
    {
        return collect();
    }

    /** Recent pond inspections. */
    public function recentInspections(): Collection
    {
        return collect();
    }

    /**
     * Quick actions exposed on the dashboard.
     * Routes are resolved defensively so this compiles before every module
     * route exists.
     *
     * @return array<int, array{label: string, route: ?string, icon: string}>
     */
    public function quickActions(): array
    {
        $candidates = [
            ['label' => 'Fish Sale', 'route' => 'sales.create', 'icon' => 'cart'],
            ['label' => 'Feed Entry', 'route' => 'feed.usages.create', 'icon' => 'feed'],
            ['label' => 'Fish Stock', 'route' => 'fish.stockings.create', 'icon' => 'fish'],
            ['label' => 'Customer Collection', 'route' => 'customers.payments.create', 'icon' => 'users'],
            ['label' => 'New Inspection', 'route' => 'fcr.inspections.create', 'icon' => 'chart'],
        ];

        return array_map(static fn(array $action): array => [
            'label' => $action['label'],
            'icon' => $action['icon'],
            'route' => \Illuminate\Support\Facades\Route::has($action['route'])
                ? $action['route']
                : null,
        ], $candidates);
    }
}
