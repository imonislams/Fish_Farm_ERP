<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Models\FeedPurchase;
use App\Models\FeedType;
use App\Models\FeedUsage;
use App\Services\Feed\FeedStockService;
use App\Services\Notification\NotificationService;

/**
 * Feed dashboard (/fish-farm/feed) — the feed position at a glance.
 *
 * Every figure comes from FeedStockService (the single definition of feed stock)
 * or a direct aggregate over the movement tables. Nothing is estimated, and a
 * feed type with no movements genuinely shows zero.
 *
 * This is deliberately NOT a second copy of the movement lists — it answers
 * "what do we have, and what is running low?", and links to the modules that
 * record the movements.
 */
class FeedController extends Controller
{
    public function __construct(
        private readonly FeedStockService $stockService,
        private readonly NotificationService $notifications,
    ) {}

    public function __invoke(): \Inertia\Response
    {
        // Raise the low/critical feed-stock alerts from the REAL stock position.
        // Idempotent per feed type per day, so opening this page repeatedly can
        // never spam the bell. This is the point the farm checks feed, so it is
        // where the proactive alert belongs.
        $this->notifications->checkLowFeedStock();

        // --- Farm-wide movement totals (real aggregates) ---------------------
        $totalPurchasedKg = (float) FeedPurchase::query()->sum('quantity_kg');
        $totalUsedKg = (float) FeedUsage::query()->sum('quantity_kg');
        $totalStockKg = $this->stockService->totalStockKg();

        // --- Per feed type stock ---------------------------------------------
        $types = FeedType::query()
            ->orderBy('name')
            ->get();

        $stockByType = $this->stockService->stockForTypes($types->pluck('id')->all());

        // Types that currently hold stock, largest first.
        $typesWithStock = $types
            ->filter(fn(FeedType $t) => ($stockByType[$t->id] ?? 0) > 0)
            ->sortByDesc(fn(FeedType $t) => $stockByType[$t->id] ?? 0)
            ->values()
            ->map(fn (FeedType $t): array => [
                'id' => $t->id,
                'name' => $t->name,
                'brand' => $t->brand,
                'unit' => $t->unit,
                'stock' => $stockByType[$t->id] ?? 0,
            ])
            ->all();

        // Types at or below their reorder level — the low-feed-stock signal.
        $lowStock = $this->stockService->lowStockTypes()
            ->map(fn (FeedType $t): array => [
                'id' => $t->id,
                'name' => $t->displayName(),
                'stock' => $stockByType[$t->id] ?? 0,
                'reorder' => (float) $t->low_stock_level_kg,
            ])
            ->values()
            ->all();

        return \Inertia\Inertia::render('Feed/Index', [
            'title' => 'Food Dashboard',
            'totalStockKg' => $totalStockKg,
            'totalPurchasedKg' => $totalPurchasedKg,
            'totalUsedKg' => $totalUsedKg,
            'typeCount' => $types->count(),
            'typesWithStock' => $typesWithStock,
            'lowStock' => $lowStock,
        ]);
    }
}
