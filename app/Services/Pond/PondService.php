<?php

namespace App\Services\Pond;

use App\Models\Pond;
use App\Models\PondType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pond business logic.
 *
 * Responsibilities (docs/ARCHITECTURE.md §3):
 *   - the filtered/paginated pond query used by every list page
 *   - the status → is_active derivation (one place, never in a controller/Blade)
 *   - real status counts for the Pond Status page
 *   - transactional writes
 *
 * The controller does request/auth/response only; no query building beyond
 * trivial lookups and no business rules live there.
 */
class PondService
{
    /**
     * Paginated, filtered pond list.
     *
     * Filters are applied server-side (Phase 2 brief §17) — the browser never
     * downloads the whole table to filter it locally. `type` is eager-loaded in
     * the same query so the view does not trigger an N+1 on `$pond->type`.
     *
     * `$filters` accepts: search, type (pond_type_id), status.
     *
     * @param  array{search?: ?string, type?: int|string|null, status?: ?string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Pond::query()
            ->with('type:id,name')                 // eager load: avoids N+1
            ->search($filters['search'] ?? null)
            ->when(
                ! empty($filters['type']),
                fn($query) => $query->ofType($filters['type'])
            )
            ->when(
                ! empty($filters['status']),
                fn($query) => $query->status($filters['status'])
            )
            ->orderBy('pond_number')
            ->paginate($perPage)
            ->withQueryString();                   // preserve filters across pages
    }

    /**
     * Create a pond.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Pond
    {
        return DB::transaction(function () use ($data): Pond {
            $pond = new Pond;

            $this->fill($pond, $data);
            $pond->save();

            return $pond;
        });
    }

    /**
     * Update a pond.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Pond $pond, array $data): Pond
    {
        return DB::transaction(function () use ($pond, $data): Pond {
            $this->fill($pond, $data);
            $pond->save();

            return $pond->refresh();
        });
    }

    /**
     * Delete a pond.
     *
     * GUARD (docs/BUSINESS_LOGIC.md §2 and docs/DATABASE.md §5): a pond that
     * still holds live fish must never be deleted — those fish would vanish with
     * no record. The user is told to harvest/clear the pond first, or to mark it
     * inactive instead. This belongs in the service because it is the write path.
     *
     * @throws \DomainException when the pond holds live stock
     */
    public function delete(Pond $pond): void
    {
        $stockService = app(\App\Services\Fish\FishStockService::class);

        if ($stockService->currentStock($pond) > 0) {
            throw new \DomainException(
                "Pond \"{$pond->name}\" still holds live fish and cannot be deleted. "
                    . 'Record a harvest or mortality to clear it, or mark it inactive instead.'
            );
        }

        DB::transaction(function () use ($pond): void {
            // Stockings / mortalities / harvests reference the pond with
            // restrictOnDelete, so a pond with any movement history still cannot
            // be removed at the database level. The live-stock check above gives
            // the clearer message for the common case.
            $pond->delete();
        });
    }

    /**
     * Human-readable reason a pond cannot be deleted, or null when it can.
     *
     * Used by the view to explain proactively; the service still enforces the
     * rule (see delete()) so the guard is never only in the UI.
     */
    public function deletionBlockReason(Pond $pond): ?string
    {
        $stockService = app(\App\Services\Fish\FishStockService::class);
        $live = $stockService->currentStock($pond);

        if ($live > 0) {
            return "This pond holds {$live} live fish. Record a harvest or mortality first, or mark it inactive.";
        }

        $movements = $pond->stockings()->count()
            + $pond->mortalities()->count()
            + $pond->harvests()->count();

        if ($movements > 0) {
            return 'This pond has stock movement history and cannot be deleted. Mark it inactive instead.';
        }

        return null;
    }

    /**
     * Real status counts for the Pond Status page and the dashboard.
     *
     * Every status in the catalogue is present in the result, defaulting to zero,
     * so the UI can render a stable set of cards. These are genuine
     * `COUNT(*) ... GROUP BY status` results — never estimated or invented.
     *
     * @return array<string, int>  status key => count
     */
    public function statusCounts(): array
    {
        $counts = Pond::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $result = [];

        foreach (array_keys(config('ponds.statuses', [])) as $status) {
            $result[$status] = (int) $counts->get($status, 0);
        }

        return $result;
    }

    /**
     * Total pond count (real database value).
     */
    public function totalCount(): int
    {
        return Pond::query()->count();
    }

    /**
     * Pond types selectable in a filter or form: id => label.
     *
     * @return Collection<int, string>
     */
    public function typeOptions(bool $activeOnly = false): Collection
    {
        return PondType::query()
            ->when($activeOnly, fn($query) => $query->active())
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * Copy the validated input onto the model, deriving `is_active` from `status`.
     *
     * `is_active` is derived rather than accepted from the form, so the boolean
     * and the status can never contradict each other (e.g. a "maintenance" pond
     * that still claims to be active).
     *
     * @param  array<string, mixed>  $data
     */
    private function fill(Pond $pond, array $data): void
    {
        $pond->fill([
            'pond_number' => $data['pond_number'],
            'name' => $data['name'],
            'pond_type_id' => $data['pond_type_id'],
            'size' => $data['size'],
            'size_unit' => $data['size_unit'],
            'depth' => $data['depth'] ?? null,
            'depth_unit' => $data['depth_unit'] ?? null,
            'location' => $data['location'] ?? null,
            'water_source' => $data['water_source'] ?? null,
            'status' => $data['status'],
            'description' => $data['description'] ?? null,
        ]);

        $pond->is_active = (bool) (config("ponds.statuses.{$data['status']}.usable") ?? false);
    }
}
