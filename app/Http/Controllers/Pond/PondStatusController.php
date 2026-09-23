<?php

namespace App\Http\Controllers\Pond;

use App\Http\Controllers\Controller;
use App\Models\Pond;
use App\Services\Pond\PondService;
use Illuminate\Http\Request;

/**
 * Pond status overview.
 *
 * This is deliberately NOT a second copy of the pond CRUD. It answers a different
 * question — "what state is the farm's pond estate in?" — by showing real
 * per-status counts and a filtered list of the ponds in the selected status.
 *
 * Every number comes from `COUNT(*) ... GROUP BY status` in PondService. Nothing
 * is estimated, and a status with no ponds genuinely shows zero (which is a real
 * value here, unlike a missing FCR — docs/BUSINESS_LOGIC.md §1).
 */
class PondStatusController extends Controller
{
    public function __construct(
        private readonly PondService $pondService,
    ) {}

    /**
     * Status dashboard, optionally drilling into one status.
     *
     * `?status=maintenance` filters the list below the cards; the cards always
     * show the full set so the user can compare and switch.
     */
    public function __invoke(Request $request): \Inertia\Response
    {
        $selected = (string) $request->query('status', '');

        $selected = array_key_exists($selected, config('ponds.statuses', []))
            ? $selected
            : '';

        $ponds = $this->pondService->paginate(
            $selected === '' ? [] : ['status' => $selected],
            (int) config('fishfarm.pagination.default', 15)
        );

        return \Inertia\Inertia::render('Ponds/Status', [
            'title' => 'Pond Status',
            'counts' => $this->pondService->statusCounts(),
            'total' => $this->pondService->totalCount(),
            'statuses' => config('ponds.statuses', []),
            'selected' => $selected,
            'ponds' => [
                'data' => collect($ponds->items())->map(fn (Pond $pond): array => [
                    'id' => $pond->id,
                    'pond_number' => $pond->pond_number,
                    'name' => $pond->name,
                    'type' => $pond->type?->name,
                    'size' => $pond->sizeDisplay(),
                    'depth' => $pond->depthDisplay(),
                    'location' => $pond->location,
                    'status' => $pond->statusLabel(),
                    'status_tone' => $pond->statusTone(),
                    'urls' => [
                        'show' => route('ponds.show', $pond, absolute: false),
                        'edit' => route('ponds.edit', $pond, absolute: false),
                    ],
                ])->all(),
                'current_page' => $ponds->currentPage(),
                'last_page' => $ponds->lastPage(),
                'total' => $ponds->total(),
                'from' => $ponds->firstItem(),
                'to' => $ponds->lastItem(),
                'links' => $ponds->linkCollection()->toArray(),
            ],
        ]);
    }
}
