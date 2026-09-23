<?php

namespace App\Services\Fish;

use App\Models\FishBatch;
use App\Models\FishMortality;
use App\Models\FishStocking;
use App\Models\Harvest;

/**
 * FishBatchService — the business logic for stocking cycles (batches).
 *
 * SINGLE SOURCE OF TRUTH (docs/BUSINESS_LOGIC.md §2):
 *   A batch is a LABEL over movement records. Its current quantity is ALWAYS
 *   derived from the stockings/mortalities/harvests tagged to it — never stored.
 *   There is no second stock ledger to keep in step with the fish module; the batch
 *   is a view over the records FishStockService already owns.
 *
 *   current quantity = Σ stockings − Σ mortalities − Σ harvests   (tagged to batch)
 */
final class FishBatchService
{
    /* ---------------------------------------------------------------------
     | Derived figures — the ONE definition per batch
    |---------------------------------------------------------------------*/

    /** Live fish in a batch: stocked − mortality − harvested (tagged to it). */
    public function currentQuantity(FishBatch $batch): int
    {
        return $this->currentQuantityForIds([$batch->getKey()])[$batch->getKey()] ?? 0;
    }

    /**
     * Batch form: current quantity for several batches in a fixed number of
     * queries. Safe for a whole list page (no N+1).
     *
     * @param  array<int, int|string>  $batchIds
     * @return array<int, int>
     */
    public function currentQuantityForIds(array $batchIds): array
    {
        if ($batchIds === []) {
            return [];
        }

        $stocked = FishStocking::query()
            ->whereIn('fish_batch_id', $batchIds)
            ->selectRaw('fish_batch_id, SUM(quantity) as aggregate')
            ->groupBy('fish_batch_id')
            ->pluck('aggregate', 'fish_batch_id');

        $died = FishMortality::query()
            ->whereIn('fish_batch_id', $batchIds)
            ->selectRaw('fish_batch_id, SUM(quantity) as aggregate')
            ->groupBy('fish_batch_id')
            ->pluck('aggregate', 'fish_batch_id');

        $harvested = Harvest::query()
            ->whereIn('fish_batch_id', $batchIds)
            ->selectRaw('fish_batch_id, SUM(quantity) as aggregate')
            ->groupBy('fish_batch_id')
            ->pluck('aggregate', 'fish_batch_id');

        $result = [];

        foreach ($batchIds as $id) {
            $result[$id] = max(
                0,
                (int) $stocked->get($id, 0)
                    - (int) $died->get($id, 0)
                    - (int) $harvested->get($id, 0),
            );
        }

        return $result;
    }

    /**
     * Survival rate (%) for a batch: current ÷ initial × 100.
     * Null when nothing was put in — there is no rate to state.
     */
    public function survivalRate(FishBatch $batch): ?float
    {
        $initial = (int) $batch->initial_quantity;

        if ($initial <= 0) {
            return null;
        }

        return round(min(100.0, $this->currentQuantity($batch) / $initial * 100), 1);
    }

    /**
     * The movement counts behind a batch, for its detail page — how the current
     * quantity was reached, without recomputing anything.
     *
     * @return array{stocked: int, mortality: int, harvested: int, transferred_note: string}
     */
    public function inputs(FishBatch $batch): array
    {
        return [
            'stocked' => (int) $batch->stockings()->sum('quantity'),
            'mortality' => (int) $batch->mortalities()->sum('quantity'),
            'harvested' => (int) $batch->harvests()->sum('quantity'),
            'transferred_note' => 'Transfers are pond-level and are not attributed to a single batch.',
        ];
    }

    /** Total batch quantity across the farm for the active cycles. */
    public function activeTotalQuantity(): int
    {
        $ids = FishBatch::query()->where('status', FishBatch::STATUS_ACTIVE)->pluck('id')->all();

        return (int) array_sum($this->currentQuantityForIds($ids));
    }

    /* ---------------------------------------------------------------------
     | Write path
    |---------------------------------------------------------------------*/

    /**
     * Create a stocking cycle. When $data carries an initial quantity AND a pond +
     * species, the opening stocking is recorded through FishStockService so the
     * batch's first movement is a REAL stocking row (single source of truth), and
     * the stocking is tagged to the batch.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FishBatch
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data): FishBatch {
            $batch = new FishBatch;
            $batch->fill([
                'pond_id' => $data['pond_id'],
                'fish_species_id' => $data['fish_species_id'],
                'code' => $data['code'] ?? $this->nextCode(),
                'started_on' => $data['started_on'],
                'ended_on' => null,
                'initial_quantity' => (int) ($data['initial_quantity'] ?? 0),
                'status' => $data['status'] ?? FishBatch::STATUS_ACTIVE,
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $batch->save();

            // Record the opening stocking as a real movement when the form supplied one.
            $initial = (int) ($data['initial_quantity'] ?? 0);

            if ($initial > 0) {
                $stocking = app(FishStockService::class)->recordStocking([
                    'pond_id' => $batch->pond_id,
                    'fish_batch_id' => $batch->getKey(),
                    'fish_species_id' => $batch->fish_species_id,
                    'quantity' => $initial,
                    'avg_weight_g' => $data['avg_weight_g'] ?? null,
                    'unit_cost' => $data['unit_cost'] ?? null,
                    'stocked_on' => $batch->started_on,
                    'supplier_name' => $data['supplier_name'] ?? null,
                    'note' => 'Opening stocking for batch ' . $batch->code,
                    'created_by' => $data['created_by'] ?? null,
                ]);

                // recordStocking() does not know the batch; tag it now.
                $stocking->forceFill(['fish_batch_id' => $batch->getKey()])->save();
            }

            return $batch;
        });
    }

    /**
     * Update a cycle's editable header fields (status, dates, note).
     * The quantity is never edited here — it lives in the movement records.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(FishBatch $batch, array $data): FishBatch
    {
        $batch->fill([
            'status' => $data['status'] ?? $batch->status,
            'ended_on' => $data['ended_on'] ?? $batch->ended_on,
            'note' => $data['note'] ?? $batch->note,
        ]);

        // A cycle that is no longer active always has an end date.
        if ($batch->status !== FishBatch::STATUS_ACTIVE && $batch->ended_on === null) {
            $batch->ended_on = now()->toDateString();
        }

        // Reopening clears the end date so the state is never contradictory.
        if ($batch->status === FishBatch::STATUS_ACTIVE) {
            $batch->ended_on = null;
        }

        $batch->save();

        return $batch;
    }

    /**
     * Delete a cycle. Refused while it still has movement records: untag or remove
     * them first, so a batch can never silently orphan fish records.
     *
     * @throws \DomainException
     */
    public function delete(FishBatch $batch): void
    {
        $movements = $batch->stockings()->count()
            + $batch->mortalities()->count()
            + $batch->harvests()->count();

        if ($movements > 0) {
            throw new \DomainException(
                "Batch \"{$batch->code}\" has {$movements} movement record(s) and cannot be deleted. "
                    . 'Mark it completed or cancelled instead.'
            );
        }

        $batch->delete();
    }

    /** Generate the next human-readable batch code, e.g. "BATCH-0007". */
    private function nextCode(): string
    {
        return sprintf('BATCH-%04d', FishBatch::query()->count() + 1);
    }

    /** The user id to attribute a write to, or null when unauthenticated. */
    public function actorId(?int $userId = null): ?int
    {
        return $userId ?? auth()->id();
    }
}
