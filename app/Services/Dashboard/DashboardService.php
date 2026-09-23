<?php

namespace App\Services\Dashboard;

use App\Models\Customer;
use App\Models\FeedStockAdjustment;
use App\Models\FeedType;
use App\Models\FeedPurchase;
use App\Models\FeedUsage;
use App\Models\FishMortality;
use App\Models\FishStocking;
use App\Models\GrowthRecord;
use App\Models\Harvest;
use App\Models\IncomeEntry;
use App\Models\ExpenseEntry;
use App\Models\Inspection;
use App\Models\Pond;
use App\Models\Purchase;
use App\Models\Sale;
use App\Services\Feed\FeedStockService;
use App\Services\Finance\CustomerBalanceService;
use App\Services\Finance\SupplierBalanceService;
use App\Services\Fish\FishStockService;
use Illuminate\Support\Carbon;

/**
 * DashboardService — every figure the dashboard shows.
 *
 * ALL calculations happen here in the database (aggregates, GROUP BY) — the React
 * page only displays what this returns. Nothing is hard-coded and nothing is
 * invented: a metric with no data is a genuine 0 / empty list.
 *
 * Version 1 is single-company (no tenant column), so "ownership" is the whole
 * farm; when a company_id is added, filter here and nowhere else.
 */
final class DashboardService
{
    public function __construct(
        private readonly CustomerBalanceService $customerBalances,
        private readonly SupplierBalanceService $supplierBalances,
        private readonly FishStockService $fishStock,
        private readonly FeedStockService $feedStock,
    ) {}

    /**
     * Resolve a date range from a preset key or explicit from/to.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public function resolveRange(string $preset = 'this_month', ?string $from = null, ?string $to = null): array
    {
        $today = now();

        [$start, $end, $label] = match ($preset) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay(), 'Today'],
            'this_week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek(), 'This week'],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
                'Last month',
            ],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfYear(), 'This year'],
            'custom' => [
                Carbon::parse($from ?: $today->copy()->startOfMonth())->startOfDay(),
                Carbon::parse($to ?: $today->copy()->endOfDay())->endOfDay(),
                'Custom range',
            ],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth(), 'This month'],
        };

        return [$start, $end, $label];
    }

    /**
     * The headline KPI tiles — all real aggregates over the chosen range.
     *
     * @return array<int, array<string, mixed>>
     */
    public function kpis(Carbon $start, Carbon $end): array
    {
        $sales = (float) Sale::query()
            ->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total');
        $salesCount = (int) Sale::query()
            ->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->count();

        $miscIncome = (float) IncomeEntry::query()
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');
        $expense = (float) ExpenseEntry::query()
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $income = round($sales + $miscIncome, 2);
        $profit = round($income - $expense, 2);

        $purchaseTotal = (float) Purchase::query()
            ->whereBetween('purchase_date', [$start->toDateString(), $end->toDateString()])
            ->sum('total');

        $receivable = $this->customerBalances->totalReceivable();
        $payable = $this->supplierBalances->totalPayable();

        $feedUsed = (float) FeedUsage::query()
            ->whereBetween('used_on', [$start->toDateString(), $end->toDateString()])
            ->sum('quantity_kg');

        return [
            $this->kpi('Sales', $sales, 'money', 'cart', "{$salesCount} sale(s) in range"),
            $this->kpi('Income', $income, 'money', 'plus', 'Sales + other income'),
            $this->kpi('Expense', $expense, 'money', 'alert', 'Recorded expenses'),
            $this->kpi('Profit / Loss', $profit, 'money', 'chart', $profit < 0 ? 'Loss in range' : 'Income − expense'),
            $this->kpi('Purchases', $purchaseTotal, 'money', 'truck', 'Supplier purchases'),
            $this->kpi('Customer Due', $receivable, 'money', 'users', 'Still owed to the farm'),
            $this->kpi('Supplier Due', $payable, 'money', 'truck', 'Still owed by the farm'),
            $this->kpi('Feed Used', $feedUsed, 'kg', 'feed', 'Consumed in range'),
        ];
    }

    /**
     * Farm-position KPIs that are not date-scoped (a live snapshot).
     *
     * @return array<int, array<string, mixed>>
     */
    public function farmKpis(): array
    {
        $ponds = Pond::query()->count();
        $activePonds = Pond::query()->where('is_active', true)->count();
        $liveFish = $this->fishStock->totalStock();
        $feedStock = $this->feedStock->totalStockKg();
        $lowFeed = $this->feedStock->lowStockTypes()->count();

        return [
            $this->kpi('Ponds', $ponds, 'int', 'droplet', "{$activePonds} active"),
            $this->kpi('Live Fish', $liveFish, 'int', 'fish', 'Stocked − mortality − harvested'),
            $this->kpi('Feed Stock', $feedStock, 'kg', 'feed', $lowFeed > 0 ? "{$lowFeed} type(s) low" : 'Above reorder level'),
            $this->kpi('Customers', Customer::query()->count(), 'int', 'users', 'On file'),
        ];
    }

    /** @return array<string, mixed> */
    private function kpi(string $label, float|int $value, string $format, string $icon, ?string $hint = null): array
    {
        return compact('label', 'value', 'format', 'icon', 'hint');
    }

    /**
     * Sales value grouped by day, oldest first — the dashboard chart series.
     *
     * @return array<int, array{date: string, label: string, value: float}>
     */
    public function salesTrend(Carbon $start, Carbon $end): array
    {
        $rows = Sale::query()
            ->whereBetween('sale_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('DATE(sale_date) as d, SUM(total) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        return $this->seriesFor($start, $end, $rows);
    }

    /**
     * Expense value grouped by day.
     *
     * @return array<int, array{date: string, label: string, value: float}>
     */
    public function expenseTrend(Carbon $start, Carbon $end): array
    {
        $rows = ExpenseEntry::query()
            ->whereBetween('entry_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('DATE(entry_date) as d, SUM(amount) as total')
            ->groupBy('d')
            ->pluck('total', 'd');

        return $this->seriesFor($start, $end, $rows);
    }

    /**
     * Build a dense day-by-day series (zero-filled) between two dates so the chart
     * has a continuous axis. Caps at 62 points to keep the payload small.
     *
     * @param  \Illuminate\Support\Collection<string, mixed>  $byDate
     * @return array<int, array{date: string, label: string, value: float}>
     */
    private function seriesFor(Carbon $start, Carbon $end, $byDate): array
    {
        $series = [];
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();
        $guard = 0;

        while ($cursor->lte($last) && $guard < 62) {
            $key = $cursor->toDateString();
            $series[] = [
                'date' => $key,
                'label' => $cursor->format('d M'),
                'value' => round((float) ($byDate[$key] ?? 0), 2),
            ];
            $cursor->addDay();
            $guard++;
        }

        return $series;
    }

    /**
     * Fish-stock composition (stocked / mortality / harvested) in range.
     *
     * @return array<int, array{label: string, value: float}>
     */
    public function stockBreakdown(Carbon $start, Carbon $end): array
    {
        $from = $start->toDateString();
        $to = $end->toDateString();

        return [
            ['label' => 'Stocked', 'value' => (float) FishStocking::query()->whereBetween('stocked_on', [$from, $to])->sum('quantity')],
            ['label' => 'Mortality', 'value' => (float) FishMortality::query()->whereBetween('recorded_on', [$from, $to])->sum('quantity')],
            ['label' => 'Harvested', 'value' => (float) Harvest::query()->whereBetween('harvested_on', [$from, $to])->sum('quantity')],
        ];
    }

    /**
     * Feed purchased vs used in range — a small two-bar comparison.
     *
     * @return array<int, array{label: string, value: float}>
     */
    public function feedBreakdown(Carbon $start, Carbon $end): array
    {
        $from = $start->toDateString();
        $to = $end->toDateString();

        $purchased = (float) FeedPurchase::query()->whereBetween('purchased_on', [$from, $to])->sum('quantity_kg');
        $used = (float) FeedUsage::query()->whereBetween('used_on', [$from, $to])->sum('quantity_kg');
        $adjusted = (float) FeedStockAdjustment::query()->whereBetween('adjusted_on', [$from, $to])->sum('quantity_kg');

        return [
            ['label' => 'Purchased', 'value' => round($purchased, 3)],
            ['label' => 'Used', 'value' => round($used, 3)],
            ['label' => 'Adjusted', 'value' => round($adjusted, 3)],
        ];
    }

    /** Low feed-stock types, real values (the "running low" signal). */
    public function lowFeedTypes(int $limit = 5): array
    {
        return $this->feedStock->lowStockTypes()
            ->take($limit)
            ->map(fn (FeedType $t): array => [
                'label' => $t->displayName(),
                'value' => (float) ($this->feedStock->currentStockKg($t)),
                'hint' => 'Reorder at ' . number_format((float) $t->low_stock_level_kg, 3) . ' kg',
            ])
            ->values()
            ->all();
    }

    /**
     * Recent activity from REAL records across every module, newest first.
     *
     * Each row is a normalised event: type, description, amount (nullable),
     * date, and an icon. Sources are merged, sorted and capped.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recentActivity(int $limit = 12): array
    {
        $events = collect();

        Sale::query()->with('customer:id,name')->latest('sale_date')->latest('id')->limit(6)->get()
            ->each(fn (Sale $s) => $events->push($this->event(
                'Sale', $s->sale_date, "Sold to " . ($s->customer?->name ?? 'a customer') . " ({$s->invoice_no})",
                (float) $s->total, 'cart',
            )));

        Purchase::query()->with('supplier:id,name')->latest('purchase_date')->latest('id')->limit(5)->get()
            ->each(fn (Purchase $p) => $events->push($this->event(
                'Purchase', $p->purchase_date, 'Bought from ' . ($p->supplier?->name ?? 'a supplier'),
                (float) $p->total, 'truck',
            )));

        IncomeEntry::query()->latest('entry_date')->latest('id')->limit(4)->get()
            ->each(fn (IncomeEntry $e) => $events->push($this->event(
                'Income', $e->entry_date, 'Income — ' . $e->categoryLabel(), (float) $e->amount, 'plus',
            )));

        ExpenseEntry::query()->latest('entry_date')->latest('id')->limit(4)->get()
            ->each(fn (ExpenseEntry $e) => $events->push($this->event(
                'Expense', $e->entry_date, 'Expense — ' . ($e->category?->name ?? 'uncategorised'), (float) $e->amount, 'alert',
            )));

        FishStocking::query()->with('pond:id,name')->latest('stocked_on')->latest('id')->limit(3)->get()
            ->each(fn (FishStocking $s) => $events->push($this->event(
                'Stocking', $s->stocked_on, "Stocked {$s->quantity} fish into " . ($s->pond?->name ?? 'a pond'),
                null, 'fish',
            )));

        FishMortality::query()->with('pond:id,name')->latest('recorded_on')->latest('id')->limit(3)->get()
            ->each(fn (FishMortality $m) => $events->push($this->event(
                'Mortality', $m->recorded_on, "{$m->quantity} mortality in " . ($m->pond?->name ?? 'a pond'),
                null, 'alert',
            )));

        Harvest::query()->with('pond:id,name')->latest('harvested_on')->latest('id')->limit(3)->get()
            ->each(fn (Harvest $h) => $events->push($this->event(
                'Harvest', $h->harvested_on, "Harvested {$h->quantity} fish from " . ($h->pond?->name ?? 'a pond'),
                null, 'truck',
            )));

        FeedUsage::query()->with('pond:id,name')->latest('used_on')->latest('id')->limit(3)->get()
            ->each(fn (FeedUsage $u) => $events->push($this->event(
                'Feed', $u->used_on, "Feed {$u->quantity_kg} kg in " . ($u->pond?->name ?? 'a pond'),
                null, 'feed',
            )));

        Inspection::query()->with('pond:id,name')->latest('inspected_on')->latest('id')->limit(3)->get()
            ->each(fn (Inspection $i) => $events->push($this->event(
                'Inspection', $i->inspected_on, 'Inspection — ' . $i->healthLabel() . ' (' . ($i->pond?->name ?? 'pond') . ')',
                null, 'chart',
            )));

        GrowthRecord::query()->with('pond:id,name')->latest('sampled_on')->latest('id')->limit(3)->get()
            ->each(fn (GrowthRecord $g) => $events->push($this->event(
                'Growth', $g->sampled_on, "Growth sample {$g->avg_weight_g} g in " . ($g->pond?->name ?? 'a pond'),
                null, 'chart',
            )));

        return $events
            ->filter(fn (array $e) => $e['date'] !== null)
            ->sortByDesc(fn (array $e) => $e['date'])
            ->take($limit)
            ->values()
            ->map(fn (array $e, int $i): array => array_merge($e, ['id' => $i + 1]))
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function event(string $type, $date, string $description, ?float $amount, string $icon): array
    {
        return [
            'id' => 0,
            'type' => $type,
            'description' => $description,
            'amount' => $amount,
            'icon' => $icon,
            'date' => $date ? Carbon::parse($date)->toDateString() : null,
            'at' => $date ? Carbon::parse($date)->format('d M Y') : null,
        ];
    }

    /**
     * Dates in the range that have real ERP events — powers the calendar dots.
     *
     * @return array<string, array<int, string>>  date => list of event types
     */
    public function calendarEvents(Carbon $start, Carbon $end): array
    {
        $from = $start->toDateString();
        $to = $end->toDateString();
        $map = [];

        $mark = function (string $column, string $table, string $type) use (&$map, $from, $to) {
            $dates = \Illuminate\Support\Facades\DB::table($table)
                ->whereBetween($column, [$from, $to])
                ->selectRaw("DATE({$column}) as d")
                ->distinct()
                ->pluck('d');
            foreach ($dates as $d) {
                $map[$d][] = $type;
            }
        };

        $mark('sale_date', 'sales', 'sales');
        $mark('purchase_date', 'purchases', 'purchases');
        $mark('entry_date', 'income_entries', 'income');
        $mark('entry_date', 'expense_entries', 'expenses');
        $mark('stocked_on', 'fish_stockings', 'stocking');
        $mark('harvested_on', 'harvests', 'harvest');
        $mark('inspected_on', 'inspections', 'inspection');

        return array_map('array_values', array_map('array_unique', $map));
    }

    /**
     * A lightweight monthly overview for the calendar widget: which days in a
     * given month have events.
     *
     * @return array<string, array<int, string>>
     */
    public function monthEvents(int $year, int $month): array
    {
        return $this->calendarEvents(
            Carbon::create($year, $month, 1)->startOfMonth(),
            Carbon::create($year, $month, 1)->endOfMonth(),
        );
    }
}
