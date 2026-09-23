<?php

namespace App\Http\Controllers\Fish;

use App\Http\Controllers\Controller;
use App\Models\FishMortality;
use App\Models\FishSpecies;
use App\Models\FishStocking;
use App\Models\Harvest;
use App\Models\Pond;
use App\Services\Fish\FishStockService;

/**
 * Fish stock dashboard — the farm's fish-stock position at a glance.
 *
 * Every figure comes from FishStockService (the single definition of "live
 * stock") or a direct aggregate over the movement tables. Nothing is estimated,
 * and a pond/species with no movements genuinely shows zero.
 *
 * This is deliberately NOT a second copy of the movement lists — it answers
 * "what do we have?", and links to the modules that record the movements.
 */
class FishStockController extends Controller
{
    public function __construct(
        private readonly FishStockService $stockService,
    ) {}

    public function __invoke(): \Inertia\Response
    {
        // --- Farm-wide movement totals (real aggregates) ---------------------
        $totalStocked = (int) FishStocking::query()->sum('quantity');
        $totalMortalities = (int) FishMortality::query()->sum('quantity');
        $totalHarvested = (int) Harvest::query()->sum('quantity');
        $liveStock = $this->stockService->totalStock();

        // --- Per-pond live stock ---------------------------------------------
        $ponds = Pond::query()
            ->with('type:id,name')
            ->orderBy('pond_number')
            ->get();

        $stockByPond = $this->stockService->stockForPonds($ponds->pluck('id')->all());

        // Only ponds that currently hold fish are listed under "live"; the rest
        // are summarised honestly rather than padded with zero rows.
        $pondsWithStock = $ponds
            ->filter(fn(Pond $p) => ($stockByPond[$p->id] ?? 0) > 0)
            ->sortByDesc(fn(Pond $p) => $stockByPond[$p->id] ?? 0)
            ->values()
            ->map(fn (Pond $p): array => [
                'id' => $p->id,
                'pond_number' => $p->pond_number,
                'name' => $p->name,
                'type' => $p->type?->name,
                'stock' => $stockByPond[$p->id] ?? 0,
                'urls' => [
                    'show' => route('ponds.show', $p, absolute: false),
                ],
            ])
            ->all();

        // --- Per-species live stock ------------------------------------------
        $stockBySpecies = $this->stockService->stockBySpecies();
        $species = FishSpecies::query()
            ->orderBy('name')
            ->get(['id', 'name', 'local_name', 'is_active'])
            ->map(fn (FishSpecies $s): array => [
                'id' => $s->id,
                'name' => $s->name,
                'local_name' => $s->local_name,
                'is_active' => (bool) $s->is_active,
                'stock' => $stockBySpecies[$s->id] ?? 0,
            ])
            ->all();

        return \Inertia\Inertia::render('Fish/Index', [
            'title' => 'Stock Dashboard',
            'liveStock' => $liveStock,
            'totalStocked' => $totalStocked,
            'totalMortalities' => $totalMortalities,
            'totalHarvested' => $totalHarvested,
            'pondCount' => $ponds->count(),
            'pondsWithStock' => $pondsWithStock,
            'stockByPond' => $stockByPond,
            'species' => $species,
            'stockBySpecies' => $stockBySpecies,
        ]);
    }
}
