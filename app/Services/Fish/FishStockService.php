<?php
namespace App\Services\Fish;

use App\Models\FishMortality;
use App\Models\FishStocking;
use App\Models\Harvest;
use App\Models\Pond;
use App\Models\PondTransfer;
use Illuminate\Support\Facades\DB;

/**
 * Fish stock movement rules — THE business logic for the fish-stock module.
 *
 * STOCK LOGIC (see docs/BUSINESS_LOGIC.md §2 — authoritative):
 *
 *   Fish Stocking  → fish stock INCREASE
 *   Mortality      → fish stock DECREASE
 *   Harvest        → fish stock DECREASE
 *
 * Invariants enforced here (the write path — docs/ARCHITECTURE.md §3):
 *   1. Stock is never changed silently — every change records its source record.
 *   2. Stock can never go negative (rejected with a DomainException BEFORE writing).
 *   3. Multi-record operations run inside DB::transaction().
 *
 * There is ONE definition of "current stock" — currentStock() below. Views,
 * lists and the pond detail page all call it; nothing recomputes the figure.
 */
final class FishStockService
{
    /** Direction constants — reused by ledgers and reports so signs stay consistent. */
    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    /**
     * Movement types and their stock direction.
     * Registering a new movement type requires adding it here first.
     *
     * @var array<string, string>
     */
    public const MOVEMENT_DIRECTIONS = [
        'stocking' => self::DIRECTION_IN,
        'mortality' => self::DIRECTION_OUT,
        'harvest' => self::DIRECTION_OUT,
        'adjustment_in' => self::DIRECTION_IN,
        'adjustment_out' => self::DIRECTION_OUT,
        'transfer_in' => self::DIRECTION_IN,
        'transfer_out' => self::DIRECTION_OUT,
    ];

    /**
     * Whether a proposed movement would drive the pond stock negative.
     * Used by validation BEFORE a write is attempted.
     *
     * @param  int  $currentStock   Fish currently alive in the pond.
     * @param  int  $quantity       Quantity to remove (positive integer).
     */
    public function wouldGoNegative(int $currentStock, int $quantity): bool
    {
        return $quantity > $currentStock;
    }

    /** Apply a signed delta to a stock figure, enforcing the non-negative rule. */
    public function applyDelta(int $currentStock, string $movementType, int $quantity): int
    {
        $direction = self::MOVEMENT_DIRECTIONS[$movementType] ?? null;

        if ($direction === null) {
            throw new \InvalidArgumentException("Unknown fish stock movement type: {$movementType}");
        }

        if ($quantity < 0) {
            throw new \InvalidArgumentException('Quantity must be a positive integer.');
        }

        $result = $direction === self::DIRECTION_IN
            ? $currentStock + $quantity
            : $currentStock - $quantity;

        if ($result < 0) {
            throw new \DomainException('Fish stock cannot go negative.');
        }

        return $result;
    }

    /* ---------------------------------------------------------------------
     | Stock calculation — the ONE definition of "current stock"
    |---------------------------------------------------------------------*/

    /** Live stock for a pond: total stocked − mortality − harvested ± transfers. */
    public function currentStock(Pond $pond): int
    {
        return $this->stockForPondId($pond->getKey());
    }

    /**
     * Batch form: live stock for several ponds in a fixed number of queries.
     *
     * @param  array<int, int|string>  $pondIds
     * @return array<int, int>  pond id => live stock
     */
    public function stockForPonds(array $pondIds): array
    {
        if ($pondIds === []) {
            return [];
        }

        $stocked = FishStocking::query()
            ->whereIn('pond_id', $pondIds)
            ->groupBy('pond_id')
            ->pluck(DB::raw('SUM(quantity)'), 'pond_id');

        $died = FishMortality::query()
            ->whereIn('pond_id', $pondIds)
            ->groupBy('pond_id')
            ->pluck(DB::raw('SUM(quantity)'), 'pond_id');

        $harvested = Harvest::query()
            ->whereIn('pond_id', $pondIds)
            ->groupBy('pond_id')
            ->pluck(DB::raw('SUM(quantity)'), 'pond_id');

        // Transfers are stored once with both sides; sum each side separately.
        $transferredIn = PondTransfer::query()
            ->whereIn('to_pond_id', $pondIds)
            ->groupBy('to_pond_id')
            ->pluck(DB::raw('SUM(quantity)'), 'to_pond_id');

        $transferredOut = PondTransfer::query()
            ->whereIn('from_pond_id', $pondIds)
            ->groupBy('from_pond_id')
            ->pluck(DB::raw('SUM(quantity)'), 'from_pond_id');

        $result = [];

        foreach ($pondIds as $id) {
            $result[$id] = max(
                0,
                (int) $stocked->get($id, 0)
                    - (int) $died->get($id, 0)
                    - (int) $harvested->get($id, 0)
                    + (int) $transferredIn->get($id, 0)
                    - (int) $transferredOut->get($id, 0)
            );
        }

        return $result;
    }

    /** Live stock for a single pond id, without loading the model. */
    public function stockForPondId(int|string $pondId): int
    {
        $stocked = (int) FishStocking::query()->where('pond_id', $pondId)->sum('quantity');
        $died = (int) FishMortality::query()->where('pond_id', $pondId)->sum('quantity');
        $harvested = (int) Harvest::query()->where('pond_id', $pondId)->sum('quantity');
        $transferredIn = (int) PondTransfer::query()->where('to_pond_id', $pondId)->sum('quantity');
        $transferredOut = (int) PondTransfer::query()->where('from_pond_id', $pondId)->sum('quantity');

        return max(0, $stocked - $died - $harvested + $transferredIn - $transferredOut);
    }

    /**
     * Total live fish across every pond — the farm-wide stock figure.
     * A genuine aggregate over the movement records, never an estimate.
     *
     * Transfers move fish between ponds, so they net to zero farm-wide and are
     * therefore not part of this total.
     */
    public function totalStock(): int
    {
        $stocked = (int) FishStocking::query()->sum('quantity');
        $died = (int) FishMortality::query()->sum('quantity');
        $harvested = (int) Harvest::query()->sum('quantity');

        return max(0, $stocked - $died - $harvested);
    }

    /**
     * Stock per species across the farm: species id => live count.
     *
     * Mortality does not record a species in this version (it is a pond-level
     * count), so only stockings and harvests — both of which DO carry a species —
     * contribute. Documented so the figure is understood, not guessed.
     *
     * @return array<int, int>
     */
    public function stockBySpecies(): array
    {
        $stocked = FishStocking::query()
            ->selectRaw('fish_species_id, SUM(quantity) as aggregate')
            ->groupBy('fish_species_id')
            ->pluck('aggregate', 'fish_species_id');

        $harvested = Harvest::query()
            ->selectRaw('fish_species_id, SUM(quantity) as aggregate')
            ->groupBy('fish_species_id')
            ->pluck('aggregate', 'fish_species_id');

        $speciesIds = $stocked->keys()->merge($harvested->keys())->unique();

        return $speciesIds->mapWithKeys(fn ($id) => [
            $id => max(0, (int) $stocked->get($id, 0) - (int) $harvested->get($id, 0)),
        ])->all();
    }

    /**
     * Throw when removing $quantity from a pond would take its stock below zero.
     *
     * @throws \DomainException
     */
    public function guardAgainstNegative(Pond $pond, int $quantity): void
    {
        $available = $this->currentStock($pond);

        if ($this->wouldGoNegative($available, $quantity)) {
            throw new \DomainException(
                "Cannot remove {$quantity} fish: pond \"{$pond->name}\" holds only {$available}."
            );
        }
    }

    /* ---------------------------------------------------------------------
     | Derived figures
    |---------------------------------------------------------------------*/

    /**
     * Derive total weight in kg from a count and an average weight in grams.
     *
     *   total_weight_kg = quantity × avg_weight_g / 1000
     *
     * Returns null when the average weight was not recorded — we never invent a
     * weight by assuming one (docs/BUSINESS_LOGIC.md §9 rule 6).
     */
    public function totalWeightKg(int $quantity, mixed $avgWeightG): ?string
    {
        if ($avgWeightG === null || $avgWeightG === '' || (float) $avgWeightG <= 0) {
            return null;
        }

        return number_format($quantity * (float) $avgWeightG / 1000, 3, '.', '');
    }

    /**
     * Derive the average weight in grams from a total weight in kg and a count.
     * Returns null when either input is missing or the count is zero.
     */
    public function avgWeightG(int $quantity, mixed $totalWeightKg): ?string
    {
        if ($quantity <= 0 || $totalWeightKg === null || $totalWeightKg === '' || (float) $totalWeightKg <= 0) {
            return null;
        }

        return number_format((float) $totalWeightKg * 1000 / $quantity, 2, '.', '');
    }

    /**
     * Total fish stocked into a pond over its whole history (stock IN only).
     * The denominator of the survival rate.
     */
    public function totalStockedForPondId(int|string $pondId): int
    {
        return (int) FishStocking::query()->where('pond_id', $pondId)->sum('quantity');
    }

    /**
     * Batch form: total stocked quantity per pond in one query. The "Stock"
     * column on the pond list, and the survival denominator.
     *
     * @param  array<int, int|string>  $pondIds
     * @return array<int, int>  pond id => total stocked
     */
    public function stockedForPonds(array $pondIds): array
    {
        if ($pondIds === []) {
            return [];
        }

        $stocked = FishStocking::query()
            ->whereIn('pond_id', $pondIds)
            ->selectRaw('pond_id, SUM(quantity) as aggregate')
            ->groupBy('pond_id')
            ->pluck('aggregate', 'pond_id');

        $result = [];

        foreach ($pondIds as $id) {
            $result[$id] = (int) $stocked->get($id, 0);
        }

        return $result;
    }

    /**
     * Batch form: survival rate (%) per pond, from the SAME definition as
     * survivalRate() (live ÷ stocked × 100), in a fixed number of queries.
     * Null where nothing was stocked — never a fabricated percentage.
     *
     * @param  array<int, int|string>  $pondIds
     * @return array<int, float|null>  pond id => survival % or null
     */
    public function survivalForPonds(array $pondIds): array
    {
        if ($pondIds === []) {
            return [];
        }

        $stocked = $this->stockedForPonds($pondIds);
        $live = $this->stockForPonds($pondIds);
        $result = [];

        foreach ($pondIds as $id) {
            $total = $stocked[$id] ?? 0;

            $result[$id] = $total <= 0
                ? null
                : round(min(100.0, ($live[$id] ?? 0) / $total * 100), 1);
        }

        return $result;
    }

    /**
     * Survival rate (%) for a pond: live fish ÷ total stocked × 100.
     *
     * Returns null when nothing was ever stocked (there is no rate to state) —
     * the UI shows an honest empty state rather than 0% or 100%.
     *
     * Note: transfers are excluded from the denominator on purpose; stocked once
     * is the correct base, and a pond that received transferred fish shows those
     * in live stock only. This mirrors how the pond detail already presents numbers.
     */
    public function survivalRate(Pond $pond): ?float
    {
        $stocked = $this->totalStockedForPondId($pond->getKey());

        if ($stocked <= 0) {
            return null;
        }

        return round(min(100.0, $this->currentStock($pond) / $stocked * 100), 1);
    }

    /**
     * Estimated live biomass (kg) for a pond.
     *
     *   biomass_kg = live fish × latest sampled avg weight (g) / 1000
     *
     * The average weight comes from the LATEST growth sample (Pond::latestAvgWeightG).
     * Returns null when the pond holds no fish or has never been sampled — we never
     * invent a biomass from an assumed weight (docs/BUSINESS_LOGIC.md §9 rule 6).
     */
    public function biomassKg(Pond $pond): ?float
    {
        $live = $this->currentStock($pond);

        if ($live <= 0) {
            return null;
        }

        $avgWeightG = $pond->latestAvgWeightG();

        if ($avgWeightG === null || $avgWeightG <= 0) {
            return null;
        }

        return round($live * $avgWeightG / 1000, 2);
    }

    /**
     * Batch form of biomassKg() for a whole list page in a fixed number of queries.
     *
     * Only ONE extra aggregate query is issued (stock), reusing stockForPonds();
     * the latest sampled weights are read per pond (one indexed query each) which
     * is the same cost as the pond detail page. Kept small deliberately.
     *
     * @param  array<int, int|string>  $pondIds
     * @return array<int, float|null>  pond id => biomass kg (null when unknown)
     */
    public function biomassForPonds(array $pondIds): array
    {
        if ($pondIds === []) {
            return [];
        }

        $stock = $this->stockForPonds($pondIds);
        $weights = Pond::query()
            ->whereIn('id', $pondIds)
            ->get(['id', 'name'])
            ->mapWithKeys(function (Pond $p): array {
                $avg = $p->latestAvgWeightG();

                return [$p->id => $avg];
            });

        $result = [];

        foreach ($pondIds as $id) {
            $live = $stock[$id] ?? 0;
            $avg = $weights[$id] ?? null;

            $result[$id] = ($live > 0 && $avg !== null && $avg > 0)
                ? round($live * $avg / 1000, 2)
                : null;
        }

        return $result;
    }

    /* ---------------------------------------------------------------------
     | Write path — each movement records its source
    |---------------------------------------------------------------------*/

    /**
     * Record a stocking (stock IN) and store the derived weight.
     *
     * @param  array<string, mixed>  $data
     */
    public function recordStocking(array $data): FishStocking
    {
        return DB::transaction(function () use ($data): FishStocking {
            $quantity = (int) $data['quantity'];
            $avgWeightG = $data['avg_weight_g'] ?? null;

            $stocking = new FishStocking;
            $stocking->fill([
                'pond_id' => $data['pond_id'],
                'fish_species_id' => $data['fish_species_id'],
                'quantity' => $quantity,
                'avg_weight_g' => $avgWeightG,
                'total_weight_kg' => $this->totalWeightKg($quantity, $avgWeightG),
                'unit_cost' => $data['unit_cost'] ?? null,
                'total_cost' => $this->totalCost($quantity, $data['unit_cost'] ?? null),
                'stocked_on' => $data['stocked_on'],
                'reference' => $data['reference'] ?? $this->nextReference('STK', FishStocking::class),
                'supplier_name' => $data['supplier_name'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $stocking->save();

            return $stocking;
        });
    }

    /**
     * Record a mortality (stock OUT).
     *
     * The non-negative guard runs inside the transaction, on a row-locked pond,
     * so two concurrent mortalities cannot both pass the check and overdraw it.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \DomainException when the amount would take stock below zero
     */
    public function recordMortality(array $data): FishMortality
    {
        return DB::transaction(function () use ($data): FishMortality {
            $pond = Pond::query()->lockForUpdate()->findOrFail($data['pond_id']);
            $quantity = (int) $data['quantity'];

            $this->guardAgainstNegative($pond, $quantity);

            $mortality = new FishMortality;
            $mortality->fill([
                'pond_id' => $pond->getKey(),
                'quantity' => $quantity,
                'avg_weight_g' => $data['avg_weight_g'] ?? null,
                'recorded_on' => $data['recorded_on'],
                'reference' => $data['reference'] ?? $this->nextReference('MOR', FishMortality::class),
                'cause' => $data['cause'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $mortality->save();

            return $mortality;
        });
    }

    /**
     * Record a harvest (stock OUT) and store the derived average weight.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \DomainException when the amount would take stock below zero
     */
    public function recordHarvest(array $data): Harvest
    {
        return DB::transaction(function () use ($data): Harvest {
            $pond = Pond::query()->lockForUpdate()->findOrFail($data['pond_id']);
            $quantity = (int) $data['quantity'];

            $this->guardAgainstNegative($pond, $quantity);

            $totalWeightKg = $data['total_weight_kg'] ?? null;

            $harvest = new Harvest;
            $harvest->fill([
                'pond_id' => $pond->getKey(),
                'fish_species_id' => $data['fish_species_id'],
                'quantity' => $quantity,
                'total_weight_kg' => $totalWeightKg,
                'avg_weight_g' => $this->avgWeightG($quantity, $totalWeightKg),
                'harvested_on' => $data['harvested_on'],
                'destination' => $data['destination'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $harvest->save();

            return $harvest;
        });
    }

    /**
     * Record a pond-to-pond transfer (stock OUT of the source, IN to the
     * destination) as ONE atomic movement.
     *
     * BUSINESS RULES (docs/BUSINESS_LOGIC.md §2a):
     *   1. Source and destination must be DIFFERENT ponds.
     *   2. Quantity must be greater than zero.
     *   3. The source must hold enough live stock — it can never go negative.
     *   4. Both sides succeed or NEITHER is written (single DB::transaction()).
     *
     * To prevent an overdraft the SOURCE pond is held under a row lock and its
     * stock re-checked; the whole movement is stored as one `pond_transfers` row
     * so the two sides cannot drift apart.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws \DomainException when the source cannot cover the quantity
     */
    public function recordTransfer(array $data): PondTransfer
    {
        return DB::transaction(function () use ($data): PondTransfer {
            $fromId = $data['from_pond_id'];
            $toId = $data['to_pond_id'];
            $quantity = (int) $data['quantity'];

            if ((int) $fromId === (int) $toId) {
                throw new \DomainException('The source and destination pond must be different.');
            }

            if ($quantity <= 0) {
                throw new \DomainException('The transfer quantity must be greater than zero.');
            }

            // Lock the source pond so two concurrent transfers cannot both pass
            // the guard and overdraw it (docs/BUSINESS_LOGIC.md §2 rule 4).
            $from = Pond::query()->lockForUpdate()->findOrFail($fromId);
            Pond::query()->findOrFail($toId);

            $this->guardAgainstNegative($from, $quantity);

            $transfer = new PondTransfer;
            $transfer->fill([
                'from_pond_id' => $from->getKey(),
                'to_pond_id' => $toId,
                'fish_species_id' => $data['fish_species_id'] ?? null,
                'quantity' => $quantity,
                'transferred_on' => $data['transferred_on'],
                'reference' => $data['reference'] ?? $this->nextReference('TRF', PondTransfer::class),
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $transfer->save();

            return $transfer;
        });
    }

    /**
     * Delete a transfer record — both sides are restored together: the source
     * gains back the quantity it shipped and the destination loses what it
     * received.
     *
     * Refused when removing the arrival would take the DESTINATION negative
     * (the fish it received have already died or been harvested).
     *
     * @throws \DomainException
     */
    public function deleteTransfer(PondTransfer $transfer): void
    {
        DB::transaction(function () use ($transfer): void {
            $destination = Pond::query()->lockForUpdate()->findOrFail($transfer->to_pond_id);
            $available = $this->currentStock($destination);

            if ($this->wouldGoNegative($available, (int) $transfer->quantity)) {
                throw new \DomainException(
                    "This transfer cannot be removed: pond \"{$destination->name}\" now holds only "
                        . "{$available} fish, fewer than the {$transfer->quantity} it received."
                );
            }

            $transfer->delete();
        });
    }

    /**
     * Delete a stocking record (stock IN, so this REMOVES stock).
     *
     * The guard refuses a deletion that would take the pond's stock negative —
     * i.e. the fish this record added have already been harvested or died.
     *
     * @throws \DomainException
     */
    public function deleteStocking(FishStocking $stocking): void
    {
        DB::transaction(function () use ($stocking): void {
            $pond = Pond::query()->lockForUpdate()->findOrFail($stocking->pond_id);
            $available = $this->currentStock($pond);

            if ($this->wouldGoNegative($available, (int) $stocking->quantity)) {
                throw new \DomainException(
                    "This stocking cannot be removed: pond \"{$pond->name}\" now holds only "
                        . "{$available} fish, fewer than the {$stocking->quantity} it added."
                );
            }

            $stocking->delete();
        });
    }

    /**
     * Delete a mortality or harvest record (both stock OUT).
     *
     * Removing an OUT movement increases the pond's stock, which can never go
     * negative — so there is nothing to guard.
     */
    public function deleteMovement(FishMortality|Harvest $movement): void
    {
        DB::transaction(function () use ($movement): void {
            $movement->delete();
        });
    }

    /** The user id to attribute a write to, or null when unauthenticated. */
    public function actorId(?int $userId = null): ?int
    {
        return $userId ?? auth()->id();
    }

    /** Derive a line cost from a fish count and a unit cost. */
    private function totalCost(int $quantity, mixed $unitCost): ?string
    {
        if ($unitCost === null || $unitCost === '') {
            return null;
        }

        return number_format($quantity * (float) $unitCost, 2, '.', '');
    }

    /**
     * Generate the next human-readable reference for a movement, e.g. "STK-0007".
     *
     * Uses the row count + 1 so references stay short and readable; uniqueness is
     * not critical (the primary key is the real identifier) but the prefix makes
     * the source of a ledger row obvious at a glance (docs/UI_GUIDELINES.md §22).
     *
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $model
     */
    private function nextReference(string $prefix, string $model): string
    {
        return sprintf('%s-%04d', $prefix, $model::query()->count() + 1);
    }
}
