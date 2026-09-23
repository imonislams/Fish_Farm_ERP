<?php

namespace App\Services\Notification;

use App\Models\ErpNotification;
use App\Services\Feed\FeedStockService;
use Illuminate\Support\Facades\Route;

/**
 * NotificationService — the ONE place ERP notifications are created and read.
 *
 * Notifications come from REAL application events (a sale recorded, a payment
 * received, low feed stock, an inspection due). Nothing is faked to "look busy":
 * if there are no events there are simply no notifications.
 *
 * The header only ever asks for a small recent slice + an unread count, so these
 * queries stay cheap on every request.
 */
final class NotificationService
{
    public function __construct(
        private readonly FeedStockService $feedStock,
    ) {}

    /**
     * Record a notification for one user (or everyone when $userId is null).
     *
     * @param  array<string, mixed>  $meta
     */
    public function notify(
        string $type,
        string $title,
        ?string $description = null,
        ?string $url = null,
        string $icon = 'bell',
        string $tone = 'info',
        ?int $userId = null,
        array $meta = [],
    ): ErpNotification {
        return ErpNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'icon' => $icon,
            'tone' => $tone,
            'meta' => $meta ?: null,
        ]);
    }

    /**
     * Convenience wrappers for the events the ERP actually produces.
     * Each is a no-op if the target route does not exist.
     */
    public function saleRecorded(string $invoice, float $total, ?int $customerId = null): void
    {
        $this->notify(
            'sale',
            'New sale recorded',
            "Invoice {$invoice} — " . number_format($total, 2) . ' total.',
            $this->routeUrl('sales.list'),
            'cart',
            'success',
            null,
            ['invoice' => $invoice, 'total' => $total, 'customer_id' => $customerId],
        );
    }

    public function purchaseRecorded(string $invoice, float $total): void
    {
        $this->notify(
            'purchase',
            'New purchase recorded',
            "Purchase {$invoice} — " . number_format($total, 2) . ' total.',
            $this->routeUrl('suppliers.purchases.index'),
            'truck',
            'info',
            null,
            ['invoice' => $invoice, 'total' => $total],
        );
    }

    public function paymentReceived(float $amount, ?string $customer = null): void
    {
        $this->notify(
            'payment',
            'Payment received',
            ($customer ? $customer . ' — ' : '') . number_format($amount, 2) . ' received.',
            $this->routeUrl('customers.payments.index'),
            'book',
            'success',
            null,
            ['amount' => $amount],
        );
    }

    public function incomeRecorded(float $amount): void
    {
        $this->notify(
            'income',
            'Income recorded',
            number_format($amount, 2) . ' added to income.',
            $this->routeUrl('finance.income.index'),
            'plus',
            'success',
            null,
            ['amount' => $amount],
        );
    }

    public function expenseRecorded(float $amount): void
    {
        $this->notify(
            'expense',
            'Expense recorded',
            number_format($amount, 2) . ' recorded as an expense.',
            $this->routeUrl('finance.expenses.index'),
            'alert',
            'warning',
            null,
            ['amount' => $amount],
        );
    }

    public function stockingRecorded(int $quantity, ?string $pond = null): void
    {
        $this->notify(
            'stocking',
            'Fish stocked',
            "{$quantity} fish stocked" . ($pond ? " into {$pond}" : '') . '.',
            $this->routeUrl('fish.stockings.index'),
            'fish',
            'info',
            null,
            ['quantity' => $quantity],
        );
    }

    public function mortalityRecorded(int $quantity, ?string $pond = null): void
    {
        $this->notify(
            'mortality',
            'Mortality recorded',
            "{$quantity} fish died" . ($pond ? " in {$pond}" : '') . '.',
            $this->routeUrl('fish.mortalities.index'),
            'alert',
            'danger',
            null,
            ['quantity' => $quantity],
        );
    }

    public function harvestRecorded(int $quantity, ?string $pond = null): void
    {
        $this->notify(
            'harvest',
            'Harvest recorded',
            "{$quantity} fish harvested" . ($pond ? " from {$pond}" : '') . '.',
            $this->routeUrl('fish.harvests.index'),
            'truck',
            'success',
            null,
            ['quantity' => $quantity],
        );
    }

    public function inspectionRecorded(string $health, ?string $pond = null): void
    {
        $this->notify(
            'inspection',
            'Pond inspected',
            ($pond ? $pond . ' — ' : '') . "health: {$health}.",
            $this->routeUrl('fcr.inspections.index'),
            'chart',
            'info',
            null,
            ['health' => $health],
        );
    }

    public function growthRecorded(float $weightG, ?string $pond = null): void
    {
        $this->notify(
            'growth',
            'Growth sample recorded',
            ($pond ? $pond . ' — ' : '') . "average {$weightG} g.",
            $this->routeUrl('fcr.growth'),
            'chart',
            'info',
            null,
            ['weight_g' => $weightG],
        );
    }

    /** A feeding was scheduled for a pond (a plan was created). */
    public function feedingScheduled(string $pond, string $time, ?string $feed = null): void
    {
        $this->notify(
            'feeding_scheduled',
            'Feeding scheduled',
            "Feeding scheduled for {$pond} at {$time}" . ($feed ? " ({$feed})" : '') . '.',
            $this->routeUrl('feed.feedings.index'),
            'calendar',
            'info',
            null,
            ['pond' => $pond, 'time' => $time],
        );
    }

    /** A feeding was actually performed (feed consumed). */
    public function feedingCompleted(string $pond, float $quantityKg, ?string $feed = null): void
    {
        $this->notify(
            'feeding_completed',
            'Feeding completed',
            "Feeding completed for {$pond} — " . number_format($quantityKg, 3) . ' kg'
                . ($feed ? " of {$feed}" : '') . '.',
            $this->routeUrl('feed.feedings.index'),
            'feed',
            'success',
            null,
            ['pond' => $pond, 'quantity_kg' => $quantityKg],
        );
    }

    /**
     * Feedings that are due today and not yet recorded — generated on demand and
     * idempotent per schedule per day, so the bell never spams.
     *
     * @return int  number of new alerts created
     */
    public function checkDueFeedings(): int
    {
        $today = now()->toDateString();
        $created = 0;

        $feedings = app(\App\Services\Feed\FeedingService::class);

        foreach ($feedings->boardForDate($today) as $row) {
            if ($row['done']) {
                continue;
            }

            $exists = ErpNotification::query()
                ->where('type', 'feeding_due')
                ->where('meta->schedule_id', $row['id'])
                ->whereDate('created_at', $today)
                ->exists();

            if ($exists) {
                continue;
            }

            $this->notify(
                'feeding_due',
                'Feeding due',
                "Feeding due for {$row['pond']} at {$row['time']}.",
                $this->routeUrl('feed.feedings.index'),
                'calendar',
                'warning',
                null,
                ['schedule_id' => $row['id'], 'pond' => $row['pond']],
            );
            $created++;
        }

        return $created;
    }

    /**
     * Low / critical feed-stock alerts — generated on demand from the REAL stock
     * position, idempotent per feed type per day so the bell never spams.
     *
     * A type at or below its CRITICAL level raises a danger alert (and only that —
     * it is not also reported as merely "low"). A type low but not critical raises
     * the warning alert. Types with no configured level are skipped.
     *
     * @return int  number of new alerts created
     */
    public function checkLowFeedStock(): int
    {
        $created = 0;
        $today = now()->toDateString();

        $criticalIds = $this->feedStock->criticalStockTypes()->pluck('id')->all();

        // Critical first, so a critical type is not double-reported as low too.
        foreach ($this->feedStock->criticalStockTypes() as $type) {
            if ($this->alertExists('feed_critical', $type->id, $today)) {
                continue;
            }

            $this->notify(
                'feed_critical',
                'Critical feed stock',
                ($type->displayName()) . ' has reached its critical level — reorder now.',
                $this->routeUrl('feed.types.index'),
                'alert',
                'danger',
                null,
                ['feed_type_id' => $type->id, 'level' => 'critical'],
            );
            $created++;
        }

        foreach ($this->feedStock->lowStockTypes() as $type) {
            // Already reported as critical today — do not also report it as low.
            if (in_array($type->id, $criticalIds, true)) {
                continue;
            }

            if ($this->alertExists('feed_low', $type->id, $today)) {
                continue;
            }

            $this->notify(
                'feed_low',
                'Low feed stock',
                ($type->displayName()) . ' is at or below its reorder level.',
                $this->routeUrl('feed.types.index'),
                'feed',
                'warning',
                null,
                ['feed_type_id' => $type->id, 'level' => 'low'],
            );
            $created++;
        }

        return $created;
    }

    /** Whether an alert of this type already exists for the feed type today. */
    private function alertExists(string $type, int|string $feedTypeId, string $date): bool
    {
        return ErpNotification::query()
            ->where('type', $type)
            ->where('meta->feed_type_id', $feedTypeId)
            ->whereDate('created_at', $date)
            ->exists();
    }

    /** Efficient unread count for the header (single COUNT query). */
    public function unreadCount(?int $userId): int
    {
        return ErpNotification::query()->forUser($userId)->unread()->count();
    }

    /**
     * The small recent slice the header dropdown shows.
     *
     * @return array<int, array<string, mixed>>
     */
    public function recent(?int $userId, int $limit = 8): array
    {
        return ErpNotification::query()
            ->forUser($userId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (ErpNotification $n): array => $this->present($n))
            ->all();
    }

    /** Mark one notification read (only if it belongs to the user). */
    public function markRead(int $id, ?int $userId): bool
    {
        return (bool) ErpNotification::query()
            ->forUser($userId)
            ->whereKey($id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /** Mark all of a user's notifications read. */
    public function markAllRead(?int $userId): int
    {
        return ErpNotification::query()
            ->forUser($userId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    /** @return array<string, mixed> */
    private function present(ErpNotification $n): array
    {
        return [
            'id' => $n->id,
            'type' => $n->type,
            'title' => $n->title,
            'description' => $n->description,
            'url' => $n->url,
            'icon' => $n->icon,
            'tone' => $n->tone,
            'read' => $n->isRead(),
            'at' => $n->created_at?->diffForHumans(),
        ];
    }

    private function routeUrl(string $name, mixed $param = null): ?string
    {
        if (! Route::has($name)) {
            return null;
        }

        return $param !== null
            ? route($name, $param, absolute: false)
            : route($name, absolute: false);
    }
}
