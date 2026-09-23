<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Finance\SaveExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use App\Services\Finance\FinanceService;
use App\Support\AsyncResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Expense categories (editable master data).
 *
 * A category in use by any expense cannot be deleted — FinanceService refuses it.
 */
class ExpenseCategoryController extends Controller
{
    public function __construct(
        private readonly FinanceService $finance,
    ) {}

    public function index(Request $request): \Inertia\Response
    {
        $search = trim((string) $request->query('search', ''));

        $categories = ExpenseCategory::query()
            ->withCount('expenses')
            ->search($search)
            ->orderBy('name')
            ->paginate((int) config('fishfarm.pagination.default', 15))
            ->withQueryString();

        return \Inertia\Inertia::render('Finance/Categories/Index', [
            'title' => 'Expense Categories',
            'categories' => [
                'data' => collect($categories->items())->map(fn (ExpenseCategory $c): array => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'description' => $c->description,
                    'expenses_count' => $c->expenses_count,
                    'is_active' => (bool) $c->is_active,
                    'deletable' => $c->isDeletable(),
                    'urls' => [
                        'edit' => route('finance.categories.edit', $c, absolute: false),
                        'destroy' => route('finance.categories.destroy', $c, absolute: false),
                    ],
                ])->all(),
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'total' => $categories->total(),
                'from' => $categories->firstItem(),
                'to' => $categories->lastItem(),
                'links' => $categories->linkCollection()->toArray(),
            ],
            'filters' => ['search' => $search],
        ]);
    }

    public function create(): \Inertia\Response
    {
        Gate::authorize('create', ExpenseCategory::class);

        return \Inertia\Inertia::render('Finance/Categories/Create', [
            'title' => 'New Expense Category',
        ]);
    }

    public function store(SaveExpenseCategoryRequest $request): RedirectResponse
    {
        $category = ExpenseCategory::create($request->validated());

        return AsyncResponse::ok(
            $request,
            "Category \"{$category->name}\" created.",
            'finance.categories.index',
        );
    }

    public function edit(ExpenseCategory $category): \Inertia\Response
    {
        Gate::authorize('update', $category);

        $category->loadCount('expenses');

        return \Inertia\Inertia::render('Finance/Categories/Edit', [
            'title' => 'Edit — ' . $category->name,
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'expenses_count' => $category->expenses_count,
                'is_active' => (bool) $category->is_active,
                'deletable' => $category->isDeletable(),
            ],
        ]);
    }

    public function update(SaveExpenseCategoryRequest $request, ExpenseCategory $category): RedirectResponse
    {
        Gate::authorize('update', $category);

        $category->update($request->validated());

        return AsyncResponse::ok(
            $request,
            "Category \"{$category->name}\" updated.",
            'finance.categories.index',
        );
    }

    public function destroy(Request $request, ExpenseCategory $category): RedirectResponse|JsonResponse
    {
        Gate::authorize('delete', $category);

        $name = $category->name;

        try {
            $this->finance->deleteCategory($category);
        } catch (\DomainException $e) {
            return AsyncResponse::refuse($request, $e->getMessage());
        }

        return AsyncResponse::ok($request, "Category \"{$name}\" deleted.", 'finance.categories.index');
    }
}
