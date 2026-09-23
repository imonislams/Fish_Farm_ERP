<?php

namespace App\Services\Fcr;

use App\Models\GrowthRecord;
use App\Models\Pond;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Growth sample business logic.
 *
 * A growth record is a sampled average weight over time. These samples are the
 * "current weight" input to FCR (docs/BUSINESS_LOGIC.md §1), so this service is
 * deliberately strict about what may be written: a sample with no weight measures
 * nothing and is refused by the request layer, never stored as 0.
 *
 * The growth trend (per-pond, per-period) is computed HERE, once, so no chart or
 * table ever recomputes it.
 */
class GrowthService
{
    /**
     * Record a sample.
     *
     * @param  array<string, mixed>  $data
     */
    public function record(array $data): GrowthRecord
    {
        return DB::transaction(function () use ($data): GrowthRecord {
            $record = new GrowthRecord;
            $record->fill([
                'pond_id' => $data['pond_id'],
                'sampled_on' => $data['sampled_on'],
                'avg_weight_g' => $data['avg_weight_g'],
                'sample_size' => $data['sample_size'] ?? null,
                'note' => $data['note'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]);
            $record->save();

            return $record;
        });
    }

    /**
     * Update a sample.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(GrowthRecord $record, array $data): GrowthRecord
    {
        return DB::transaction(function () use ($record, $data): GrowthRecord {
            $record->fill([
                'pond_id' => $data['pond_id'],
                'sampled_on' => $data['sampled_on'],
                'avg_weight_g' => $data['avg_weight_g'],
                'sample_size' => $data['sample_size'] ?? null,
                'note' => $data['note'] ?? null,
            ])->save();

            return $record->refresh();
        });
    }

    /** Delete a sample. Nothing else references it, so no guard is needed. */
    public function delete(GrowthRecord $record): void
    {
        DB::transaction(function () use ($record): void {
            $record->delete();
        });
    }

    /* ---------------------------------------------------------------------
     | Derived figures — computed once, never in a view
    |---------------------------------------------------------------------*/

    /**
     * The growth trend for a pond, oldest first — the points a chart plots.
     *
     * @return Collection<int, array{date: string, label: string, avg_weight_g: float, kg: float}>
     */
    public function trendForPond(Pond $pond, int $limit = 60): Collection
    {
        return GrowthRecord::query()
            ->where('pond_id', $pond->getKey())
            ->orderByDesc('sampled_on')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->sortBy('sampled_on')
            ->values()
            ->map(fn(GrowthRecord $r): array => [
                'date' => $r->sampled_on?->toDateString() ?? '',
                'label' => $r->sampled_on?->format('d M') ?? '',
                'avg_weight_g' => round((float) $r->avg_weight_g, 2),
                'kg' => round((float) $r->avg_weight_g / 1000, 4),
            ]);
    }

    /**
     * Growth summary for a pond: first sample, latest sample, gain and the daily
     * rate between them.
     *
     * A null gain means there were fewer than two samples — growth cannot be
     * established yet, which is different from a gain of zero.
     *
     * @return array{first_g: ?float, latest_g: ?float, gain_g: ?float, daily_gain_g: ?float, samples: int, span_days: ?int}
     */
    public function summaryForPond(Pond $pond): array
    {
        $records = GrowthRecord::query()
            ->where('pond_id', $pond->getKey())
            ->orderBy('sampled_on')
            ->orderBy('id')
            ->get();

        if ($records->isEmpty()) {
            return [
                'first_g' => null,
                'latest_g' => null,
                'gain_g' => null,
                'daily_gain_g' => null,
                'samples' => 0,
                'span_days' => null,
            ];
        }

        $first = $records->first();
        $latest = $records->last();

        $spanDays = $first->sampled_on && $latest->sampled_on
            ? (int) $first->sampled_on->diffInDays($latest->sampled_on)
            : null;

        // A single sample has no gain to report — null, not zero.
        $gainG = $records->count() > 1
            ? round((float) $latest->avg_weight_g - (float) $first->avg_weight_g, 2)
            : null;

        // Guarded division: no span means no daily rate.
        $dailyGainG = ($gainG !== null && $spanDays > 0)
            ? round($gainG / $spanDays, 3)
            : null;

        return [
            'first_g' => round((float) $first->avg_weight_g, 2),
            'latest_g' => round((float) $latest->avg_weight_g, 2),
            'gain_g' => $gainG,
            'daily_gain_g' => $dailyGainG,
            'samples' => $records->count(),
            'span_days' => $spanDays,
        ];
    }

    /**
     * Growth summary for several ponds in a fixed number of queries.
     *
     * @param  array<int, int|string>  $pondIds
     * @return array<int, array<string, mixed>>
     */
    public function summariesForPonds(array $pondIds): array
    {
        if ($pondIds === []) {
            return [];
        }

        $all = GrowthRecord::query()
            ->whereIn('pond_id', $pondIds)
            ->orderBy('sampled_on')
            ->orderBy('id')
            ->get(['id', 'pond_id', 'sampled_on', 'avg_weight_g'])
            ->groupBy('pond_id');

        $out = [];

        foreach ($pondIds as $id) {
            $records = $all->get($id, collect());

            if ($records->isEmpty()) {
                $out[$id] = [
                    'first_g' => null,
                    'latest_g' => null,
                    'gain_g' => null,
                    'daily_gain_g' => null,
                    'samples' => 0,
                    'span_days' => null,
                ];

                continue;
            }

            $first = $records->first();
            $latest = $records->last();
            $spanDays = $first->sampled_on && $latest->sampled_on
                ? (int) $first->sampled_on->diffInDays($latest->sampled_on)
                : null;
            $gainG = $records->count() > 1
                ? round((float) $latest->avg_weight_g - (float) $first->avg_weight_g, 2)
                : null;

            $out[$id] = [
                'first_g' => round((float) $first->avg_weight_g, 2),
                'latest_g' => round((float) $latest->avg_weight_g, 2),
                'gain_g' => $gainG,
                'daily_gain_g' => ($gainG !== null && $spanDays !== null && $spanDays > 0)
                    ? round($gainG / $spanDays, 3)
                    : null,
                'samples' => $records->count(),
                'span_days' => $spanDays,
            ];
        }

        return $out;
    }

    /** The user id to attribute a write to, or null when unauthenticated. */
    public function actorId(?int $userId = null): ?int
    {
        return $userId ?? auth()->id();
    }
}
