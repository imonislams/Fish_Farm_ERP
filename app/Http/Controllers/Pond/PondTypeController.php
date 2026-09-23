<?php

namespace App\Http\Controllers\Pond;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pond\StorePondTypeRequest;
use App\Http\Requests\Pond\UpdatePondTypeRequest;
use App\Models\PondType;
use App\Services\Pond\PondTypeService;
use App\Support\AsyncResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Pond type management (master data).
 *
 * Thin controller: authorize, delegate to PondTypeService, return a view or a
 * redirect with a flash message. The "type in use cannot be deleted" business
 * rule lives in the service — this controller only turns the resulting
 * DomainException into a clear flash error rather than a stack trace.
 *
 * AUTHORIZATION: `permission:pond_type.*` middleware on every route plus the
 * policy check inside each action.
 */
class PondTypeController extends Controller
{
    public function __construct(
        private readonly PondTypeService $pondTypeService,
    ) {}

    /** Paginated, searchable pond type list with pond counts (Inertia/React). */
    public function index(Request $request): \Inertia\Response
    {
        $search = trim((string) $request->query('search', ''));

        $types = PondType::query()
            ->withCount('ponds')                    // avoids a count query per row
            ->when($search !== '', fn($query) => $query->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Ponds/Types/Index', [
            'title' => 'Pond Types',
            'types' => [
                'data' => collect($types->items())->map(fn (PondType $type): array => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'description' => $type->description,
                    'ponds_count' => $type->ponds_count,
                    'is_active' => (bool) $type->is_active,
                    'created_at' => $type->created_at?->format('d M Y'),
                    'urls' => [
                        'edit' => route('ponds.types.edit', $type, absolute: false),
                        'destroy' => route('ponds.types.destroy', $type, absolute: false),
                    ],
                ])->all(),
                'current_page' => $types->currentPage(),
                'last_page' => $types->lastPage(),
                'total' => $types->total(),
                'from' => $types->firstItem(),
                'to' => $types->lastItem(),
                'links' => $types->linkCollection()->toArray(),
            ],
            'filters' => ['search' => $search],
        ]);
    }

    /** Show the create form (Inertia/React). */
    public function create(): \Inertia\Response
    {
        Gate::authorize('create', PondType::class);

        return \Inertia\Inertia::render('Ponds/Types/Create', [
            'title' => 'New Pond Type',
        ]);
    }

    /** Persist a new pond type. */
    public function store(StorePondTypeRequest $request): RedirectResponse
    {
        $type = $this->pondTypeService->create($request->validated());

        return redirect()
            ->route('ponds.types.index')
            ->with('success', "Pond type \"{$type->name}\" created.");
    }

    /** Show the edit form (Inertia/React). */
    public function edit(PondType $pondType): \Inertia\Response
    {
        Gate::authorize('update', $pondType);

        $pondType->loadCount('ponds');

        return \Inertia\Inertia::render('Ponds/Types/Edit', [
            'title' => 'Edit — ' . $pondType->name,
            'type' => [
                'id' => $pondType->id,
                'name' => $pondType->name,
                'description' => $pondType->description,
                'ponds_count' => $pondType->ponds_count,
                'is_active' => (bool) $pondType->is_active,
                'created_at' => $pondType->created_at?->format('d M Y'),
            ],
        ]);
    }

    /** Persist changes. */
    public function update(UpdatePondTypeRequest $request, PondType $pondType): RedirectResponse
    {
        Gate::authorize('update', $pondType);

        $this->pondTypeService->update($pondType, $request->validated());

        return redirect()
            ->route('ponds.types.index')
            ->with('success', "Pond type \"{$pondType->name}\" updated.");
    }

    /**
     * Delete a pond type.
     *
     * A type still referenced by ponds is refused with an explanation — never
     * deleted silently, and never leaving orphaned ponds behind.
     */
    public function destroy(Request $request, PondType $pondType): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('delete', $pondType);

        $name = $pondType->name;

        try {
            $this->pondTypeService->delete($pondType);
        } catch (\DomainException $e) {
            return AsyncResponse::refuse($request, $e->getMessage());
        }

        return AsyncResponse::ok($request, "Pond type \"{$name}\" deleted.", 'ponds.types.index');
    }
}
