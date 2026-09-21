<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardMetricsService;
use Illuminate\Contracts\View\View;

/**
 * Dashboard.
 *
 * Controller responsibilities (docs/ARCHITECTURE.md):
 *   - handle the request
 *   - call services for data
 *   - return the view
 *
 * No business calculations live here.
 */
class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardMetricsService $metrics,
    ) {}

    public function index(): View
    {
        return view('dashboard.index', [
            'title' => 'Dashboard',
            'kpis' => $this->metrics->kpis(),
            'analytics' => $this->metrics->analytics(),
            'recentTransactions' => $this->metrics->recentTransactions(),
            'recentInspections' => $this->metrics->recentInspections(),
            'quickActions' => $this->metrics->quickActions(),
        ]);
    }
}
