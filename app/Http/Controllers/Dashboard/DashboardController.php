<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Inertia\Response;

/**
 * Dashboard.
 *
 * Every figure is computed by DashboardService (real aggregates); this controller
 * only resolves the date filter and serialises the result for React. It performs
 * no calculations itself.
 *
 * The date filter is a normal Inertia GET with `from`/`to` query params, so the
 * shell never reloads and the range survives refresh/back/bookmarks.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboard,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): Response
    {
        // Raise the proactive alerts from the REAL stock/schedule position. Both
        // are idempotent per day, so loading the dashboard repeatedly never spams
        // the bell — it only surfaces conditions that are genuinely true now.
        $this->notifications->checkLowFeedStock();
        $this->notifications->checkDueFeedings();

        $preset = (string) $request->query('range', 'this_month');
        $from = $request->query('from');
        $to = $request->query('to');

        [$start, $end, $label] = $this->dashboard->resolveRange($preset, $from, $to);

        return \Inertia\Inertia::render('Dashboard', [
            'title' => 'Dashboard',
            'range' => [
                'preset' => $preset,
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
                'label' => $label,
            ],
            'kpis' => $this->dashboard->kpis($start, $end),
            'farmKpis' => $this->dashboard->farmKpis(),
            'charts' => [
                'salesTrend' => $this->dashboard->salesTrend($start, $end),
                'expenseTrend' => $this->dashboard->expenseTrend($start, $end),
                'stock' => $this->dashboard->stockBreakdown($start, $end),
                'feed' => $this->dashboard->feedBreakdown($start, $end),
            ],
            'lowFeedTypes' => $this->dashboard->lowFeedTypes(),
            'recentActivity' => $this->dashboard->recentActivity(12),
            'calendarEvents' => $this->dashboard->calendarEvents($start, $end),
        ]);
    }
}
