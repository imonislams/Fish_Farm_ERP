<?php
namespace App\Services\Feed;

use App\Models\FeedPurchase;
use App\Models\FeedStockAdjustment;
use App\Models\FeedType;
use App\Models\FeedUsage;
use Illuminate\Support\Facades\DB;

/**
 * Feed stock movement rules — THE business logic for the feed module.
 *
 * STOCK LOGIC (see docs/BUSINESS_LOGIC.md §2 — authoritative):
 *
 *   Feed Purchase    → feed stock INCREASE
 *   Feed Usage       → feed stock DECREASE
 *   Stock Adjustment → feed stock INCREASE or DECREASE (always with a reason)
 *   Wastage          → feed stock DECREASE
 *
 * Invariants enforced here (the write path — docs/ARCHITECTURE.md §3):
 *   1. Stock is never changed silently — every change is a recorded movement.
 *   2. Stock can never go negative (rejected BEFORE writing).
 *   3. Multi-record operations run inside DB::transaction().
 *   4. Feed is measured in kg, rounded to 3 decimals.
 *   5. Low-stock thresholds raise the low-feed-stock signal (`isLow()`).
 *
 * There is ONE definition of "current stock" — currentStockKg() below. Views,
 * lists and the feed dashboard all call it; nothing recomputes the figure.
 */
final class FeedStockService
{
    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    /** @var array<string, string> */
    public const MOVEMENT_DIRECTIONS = [
        'purchase' => self::DIRECTION_IN,
        'usage' => self::DIRECTION_OUT,
        'adjustment_in' => self::DIRECTION_IN,
        'adjustment_out' => self::DIRECTION_OUT,
        'transfer_in' => self::DIRECTION_IN,
        'transfer_out' => self::DIRECTION_OUT,
        'wastage' => self::DIRECTION_OUT,
    ];

    public function wouldGoNegative(float $currentStockKg, float $quantityKg): bool
    {
        return $quantityKg > $currentStockKg;
    }

    /**
     * Apply a signed delta to a feed stock figure.
     * Feed is measured in kilograms and kept to 3 decimal places.
     */
    public function applyDelta(float $currentStockKg, string $movementType, float $quantityKg): float
    {
        $direction = self::MOVEMENT_DIRECTIONS[$movementType] ?? null;

        if ($direction === null) {
            throw new \InvalidArgumentException("Unknown feed stock movement type: {$movementType}");
        }

        if ($quantityKg < 0) {
            throw new \InvalidArgumentException('Quantity must be a positive number.');
        }

        $result = $direction === self::DIRECTION_IN
            ? $currentStockKg + $quantityKg
            : $currentStockKg - $quantityKg;

        if ($result < 0) {
            throw new \DomainException('Feed stock cannot go negative.');
        }

        return round($result, 3);
    }

    /** True when stock has reached or fallen below the configured low-stock level. */
    public function isLow(float $currentStockKg, ?float $lowStockLevelKg): bool
    {
        return $lowStockLevelKg !== null && $currentStockKg <= $lowStockLevelKg;
    }

    /**
     * True when stock has reached or fallen below the configured CRITICAL level.
     * A critical level is stricter than the low level: it means the type is about
     * to run out. Null level = no critical tier.
     */
    public function isCritical(float $currentStockKg, ?float $criticalStockLevelKg): bool
    {
        return $criticalStockLevelKg !== null && $currentStockKg <= $criticalStockLevelKg;
    }

    /**
     * Derive a line cost from a quantity and a unit cost.
     * Returns null when no unit cost was given — we never assume a price.
     */
    public function totalCost(mixed $quantityKg, mixed $unitCost): ?string
    {
        if ($unitCost === null || $unitCost === '' || $quantityKg === null || $quantityKg === '') {
            return null;
        }

        return number_format((float) $quantityKg * (float) $unitCost, 2, '.', '');
    }

    /* ---------------------------------------------------------------------
     | Stock calculation — the ONE definition of "current stock"
    |---------------------------------------------------------------------*/

    /** Current stock for one feed type, in kg. */
    public function currentStockKg(FeedType $type): float
    {
        return $this->stockForTypeId($type->getKey());
    }

    /** Current stock for a feed type id, without loading the model. */
    public function stockForTypeId(int|string $typeId): float
    {
        return $this->stockForTypes([$typeId])[$typeId] ?? 0.0;
    }

    /**
     * Batch form: current stock for several feed types in a fixed number of
     * queries. Safe to call for a whole list page (no N+1).
     *
     * stock = Σ purchases + Σ adjustment-in − Σ usages − Σ adjustment-out
     *
     * @param  array<int, int|string>  $typeIds
     * @return array<int, float>  feed type id => stock in kg
     */
    public function stockForTypes(array $typeIds): array
    {
        if ($typeIds === []) {
            return [];
        }

        $purchased = FeedPurchase::query()
            ->whereIn('feed_type_id', $typeIds)
            ->selectRaw('feed_type_id, SUM(quantity_kg) as aggregate')
            ->groupBy('feed_type_id')
            ->pluck('aggregate', 'feed_type_id');

        $used = FeedUsage::query()
            ->whereIn('feed_type_id', $typeIds)
            ->selectRaw('feed_type_id, SUM(quantity_kg) as aggregate')
            ->groupBy('feed_type_id')
            ->pluck('aggregate', 'feed_type_id');

        $adjustIn = FeedStockAdjustment::query()
            ->whereIn('feed_type_id', $typeIds)
            ->where('direction', self::DIRECTION_IN)
            ->selectRaw('feed_type_id, SUM(quantity_kg) as aggregate')
            ->groupBy('feed_type_id')
            ->pluck('aggregate', 'feed_type_id');

        $adjustOut = FeedStockAdjustment::query()
            ->whereIn('feed_type_id', $typeIds)
            ->where('direction', self::DIRECTION_OUT)
            ->selectRaw('feed_type_id, SUM(quantity_kg) as aggregate')
            ->groupBy('feed_type_id')
            ->pluck('aggregate', 'feed_type_id');

        $result = [];

        foreach ($typeIds as $id) {
            $stock = (float) $purchased->get($id, 0)
                + (float) $adjustIn->get($id, 0)
                - (float) $used->get($id, 0)
                - (float) $adjustOut->get($id, 0);

            // Defensive clamp: writes are guarded, so this should never be needed.
            $result[$id] = round(max(0, $stock), 3);
        }

        return $result;
    }

    /**
     * Total feed stock across every feed type, in kg — the farm-wide figure.
     * A genuine aggregate over the movement records, never an estimate.
     */
    public function totalStockKg(): float
    {
        $purchased = (float) FeedPurchase::query()->sum('quantity_kg');
        $used = (float) FeedUsage::query()->sum('quantity_kg');
        $adjustIn = (float) FeedStockAdjustment::query()->where('direction', self::DIRECTION_IN)->sum('quantity_kg');
        $adjustOut = (float) FeedStockAdjustment::query()->where('direction', self::DIRECTION_OUT)->sum('quantity_kg');

        return round(max(0, $purchased + $adjustIn - $used - $adjustOut), 3);
    }

    /**
     * Feed types whose stock has reached or fallen below their low-stock level.
     *
     * Uses the batch stock computation, so it is a fixed number of queries
     * regardless of how many types exist.
     *
     * @return \Illuminate\Support\Collection<int, FeedType>
     */
    public function lowStockTypes(): \Illuminate\Support\Collection
    {
        $types = FeedType::query()->active()->get();

        if ($types->isEmpty()) {
            return collect();
        }

        $stock = $this->stockForTypes($types->pluck('id')->all());

        return $types->filter(function (FeedType $type) use ($stock): bool {
            $level = $type->low_stock_level_kg;

            if ($level === null) {
                return false;
            }

            return $this->isLow($stock[$type->id] ?? 0.0, (float) $level);
        })->values();
    }

    /**
     * Feed types at or below their CRITICAL level — the "about to run out" signal,
     * stricter than lowStockTypes(). Fixed number of queries (batch stock).
     *
     * @return \Illuminate\Support\Collection<int, FeedType>
     */
    public function criticalStockTypes(): \Illuminate\Support\Collection
    {
        $types = FeedType::query()->active()->get();

        if ($types->isEmpty()) {
            return collect();
        }

        $stock = $this->stockForTypes($types->pluck('id')->all());

        return $types->filter(function (FeedType $type) use ($stock): bool {
            $level = $type->critical_stock_level_kg;

            if ($level === null) {
                return false;
            }

            return $this->isCritical($stock[$type->id] ?? 0.0, (float) $level);
        })->values();
    }

    /* ---------------------------------------------------------------------
     | Guards (used before a write is attempted)
    |---------------------------------------------------------------------*/

    /**
     * Throw when removing $quantityKg from a feed type would take its stock
     * below zero.
     *
     * @throws \DomainException
     */
    public function guardAgainstNegative(FeedType $type, float $quantityKg): void
    {
        $available = $this->currentStockKg($type);

        if ($this->wouldGoNegative($available, $quantityKg)) {
            throw new \DomainException(
                "Cannot remove {$quantityKg} kg: \"{$type->name}\" holds only {$available} kg."
            );
        }
    }

    /* ---------------------------------------------------------------------
     | Write path — each movement records its source
    |---------------------------------------------------------------------*/

    /**
     * Record a purchase (stock IN) and store the derived line cost.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordPurchase(array $data): FeedPurchase
    {
        return DB::transaction(function () use ($data): FeedPurchase {
            $quantityKg = (float) $data['quantity_kg'];
            $unitCost = $data['unit_cost'] ?? null;

            $purchase = new FeedPurchase;
            $purchase->fill([
                'feed_type_id' => $data['feed_type_id'],
                'quantity_kg' => $quantityKg,
                'unit_cost' => $unitCost,
                'total_cost' => $this->totalCost($quantityKg, $unitCost),
                'purchased_on' => $data['purchased_on'],
                'invoice_no' => $data['invoice_no'] ?? null,
                'supplier_name' => $data['supplier_name'] ?? null,
                'paid_amount' => $data['paid_amount'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $purchase->save();

            return $purchase;
        });
    }

    /**
     * Record a usage (stock OUT).
     *
     * The non-negative guard runs inside the transaction on a row-locked feed
     * type, so two concurrent usages cannot both pass the check and overdraw it.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \DomainException when the amount would take stock below zero
     */
    public function recordUsage(array $data): FeedUsage
    {
        return DB::transaction(function () use ($data): FeedUsage {
            $type = FeedType::query()->lockForUpdate()->findOrFail($data['feed_type_id']);
            $quantityKg = (float) $data['quantity_kg'];

            $this->guardAgainstNegative($type, $quantityKg);

            $usage = new FeedUsage;
            $usage->fill([
                'pond_id' => $data['pond_id'],
                'feed_type_id' => $type->getKey(),
                'quantity_kg' => $quantityKg,
                'used_on' => $data['used_on'],
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $usage->save();

            return $usage;
        });
    }

    /**
     * Record a manual adjustment (stock IN or OUT) WITH its reason.
     *
     * A reason and a direction are required by the request; this method derives
     * the movement type from the direction so the sign convention stays in one
     * place.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \DomainException when an OUT adjustment would take stock negative
     */
    public function recordAdjustment(array $data): FeedStockAdjustment
    {
        return DB::transaction(function () use ($data): FeedStockAdjustment {
            $type = FeedType::query()->lockForUpdate()->findOrFail($data['feed_type_id']);
            $quantityKg = (float) $data['quantity_kg'];

            if ($data['direction'] === FeedStockAdjustment::DIRECTION_OUT) {
                $this->guardAgainstNegative($type, $quantityKg);
            }

            $adjustment = new FeedStockAdjustment;
            $adjustment->fill([
                'feed_type_id' => $type->getKey(),
                'direction' => $data['direction'],
                'quantity_kg' => $quantityKg,
                'reason' => $data['reason'],
                'note' => $data['note'] ?? null,
                'adjusted_on' => $data['adjusted_on'],
                'created_by' => $data['created_by'] ?? null,
            ]);
            $adjustment->save();

            return $adjustment;
        });
    }

    /**
     * Delete a purchase record (stock IN, so this REMOVES stock).
     *
     * Refused when the feed it added has already been used or adjusted away.
     *
     * @throws \DomainException
     */
    public function deletePurchase(FeedPurchase $purchase): void
    {
        DB::transaction(function () use ($purchase): void {
            $type = FeedType::query()->lockForUpdate()->findOrFail($purchase->feed_type_id);
            $available = $this->currentStockKg($type);

            if ($this->wouldGoNegative($available, (float) $purchase->quantity_kg)) {
                throw new \DomainException(
                    "This purchase cannot be removed: \"{$type->name}\" now holds only "
                        . "{$available} kg, fewer than the {$purchase->quantity_kg} kg it added."
                );
            }

            $purchase->delete();
        });
    }

    /**
     * Delete a usage or an OUT adjustment (both stock OUT).
     *
     * Removing an OUT movement increases stock, which can never go negative — so
     * there is nothing to guard. Wrapped in a transaction for atomicity.
     */
    public function deleteOutMovement(FeedUsage|FeedStockAdjustment $movement): void
    {
        DB::transaction(function () use ($movement): void {
            $movement->delete();
        });
    }

    /**
     * Delete an adjustment (either direction).
     *
     * An IN adjustment, like a purchase, adds stock — deleting it can be refused
     * when the stock is already gone. An OUT adjustment needs no guard.
     *
     * @throws \DomainException
     */
    public function deleteAdjustment(FeedStockAdjustment $adjustment): void
    {
        if (! $adjustment->isIncrease()) {
            $this->deleteOutMovement($adjustment);

            return;
        }

        DB::transaction(function () use ($adjustment): void {
            $type = FeedType::query()->lockForUpdate()->findOrFail($adjustment->feed_type_id);
            $available = $this->currentStockKg($type);

            if ($this->wouldGoNegative($available, (float) $adjustment->quantity_kg)) {
                throw new \DomainException(
                    "This adjustment cannot be removed: \"{$type->name}\" now holds only "
                        . "{$available} kg, fewer than the {$adjustment->quantity_kg} kg it added."
                );
            }

            $adjustment->delete();
        });
    }

    /** The user id to attribute a write to, or null when unauthenticated. */
    public function actorId(?int $userId = null): ?int
    {
        return $userId ?? auth()->id();
    }
}
