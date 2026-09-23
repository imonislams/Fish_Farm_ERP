<?php

use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\Customer\CustomerDueController;
use App\Http\Controllers\Customer\CustomerPaymentController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Fcr\ComparisonController;
use App\Http\Controllers\Fcr\FcrController;
use App\Http\Controllers\Fcr\FcrReportController;
use App\Http\Controllers\Fcr\FeedGrowthController;
use App\Http\Controllers\Fcr\GrowthController;
use App\Http\Controllers\Fcr\InspectionController;
use App\Http\Controllers\Fcr\InspectionScheduleController;
use App\Http\Controllers\Feed\FeedAdjustmentController;
use App\Http\Controllers\Feed\FeedController;
use App\Http\Controllers\Feed\FeedingController;
use App\Http\Controllers\Feed\FeedPurchaseController;
use App\Http\Controllers\Feed\FeedTypeController;
use App\Http\Controllers\Feed\FeedUsageController;
use App\Http\Controllers\Finance\ExpenseCategoryController;
use App\Http\Controllers\Finance\ExpenseController;
use App\Http\Controllers\Finance\FinanceReportController;
use App\Http\Controllers\Finance\IncomeController;
use App\Http\Controllers\Fish\FishBatchController;
use App\Http\Controllers\Fish\FishMortalityController;
use App\Http\Controllers\Fish\FishSpeciesController;
use App\Http\Controllers\Fish\FishStockController;
use App\Http\Controllers\Fish\FishStockingController;
use App\Http\Controllers\Fish\HarvestController;
use App\Http\Controllers\Party\PartyController;
use App\Http\Controllers\Party\PartyLedgerController;
use App\Http\Controllers\Party\PartyTransactionController;
use App\Http\Controllers\Pond\MortalityController;
use App\Http\Controllers\Pond\PondController;
use App\Http\Controllers\Pond\PondLedgerController;
use App\Http\Controllers\Pond\PondLedgerEntryController;
use App\Http\Controllers\Pond\PondStatusController;
use App\Http\Controllers\Pond\PondTypeController;
use App\Http\Controllers\Pond\StockingController;
use App\Http\Controllers\Pond\TransferController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Sales\SaleController;
use App\Http\Controllers\Supplier\PurchaseController;
use App\Http\Controllers\Supplier\SupplierController;
use App\Http\Controllers\Supplier\SupplierDueController;
use App\Http\Controllers\Supplier\SupplierPaymentController;
use App\Http\Controllers\Settings\CompanyController;
use App\Http\Controllers\Settings\PermissionController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Settings\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Fish Farm ERP
|--------------------------------------------------------------------------
|
| ROUTE ARCHITECTURE (docs/ROUTES.md is authoritative):
|
|   /dashboard                       → dashboard
|
|   /fish-farm/ponds                 → ponds.*
|   /fish-farm/pond-ledger           → ledger.*
|   /fish-farm/feed                  → feed.*
|   /fish-farm/fcr                   → fcr.*
|   /fish-farm/fish                  → fish.*
|   /fish-farm/sales                 → sales.*
|   /fish-farm/customers             → customers.*
|   /fish-farm/suppliers             → suppliers.*
|   /fish-farm/parties               → parties.*
|   /fish-farm/finance               → finance.*
|   /fish-farm/reports               → reports.*
|
|   /settings                        → settings.*
|
| RULES
|   - Every route MUST have a name. React resolves URLs from the shared `routes`
|     prop (never a hard-coded URL); Blade uses route() names.
|   - Route names follow the module prefix (ponds.index, ponds.create, …).
|   - Authorization middleware/policies attach per group; every controller also
|     re-asserts the policy, so an id in the URL cannot reach a record the user
|     is not permitted to act on.
|   - Literal segments (/create, /export, /types) are declared BEFORE any
|     {wildcard} so they are never captured by model binding.
|
| Every module is implemented and returns an Inertia/React page; no route renders
| a placeholder or fakes data.
|
| Auth routes live in routes/auth.php, loaded by bootstrap/app.php.
*/

/* ------------------------------------------------------------------ root -- */

Route::redirect('/', '/dashboard');

/* ------------------------------------------------------------------- PWA --- */
/* Offline shell is served by public/sw.js; this route gives the SW a named
   fallback in the route table and can be linked from the UI. */
Route::view('/offline', 'placeholders.offline')->name('offline');

/* -------------------------------------------- authenticated module routes -- */
/*
| The `auth` middleware shapes the final routing layout. It only becomes active
| once the authentication module is implemented (routes/auth.php + login views).
| Until then this group is unreachable — intentional. See docs/PROJECT.md
| (open decisions).
*/
Route::middleware(['auth'])->group(function () {

    /* ----------------------------------------------------------- Dashboard -- */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboard.view')
        ->name('dashboard');

    /* ------------------------------------------- Notifications & calendar -- */
    Route::middleware('auth')->group(function () {
        Route::get('/notifications', [\App\Http\Controllers\Notification\NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::patch('/notifications/{notification}/read', [\App\Http\Controllers\Notification\NotificationController::class, 'markRead'])
            ->name('notifications.read');
        Route::post('/notifications/read-all', [\App\Http\Controllers\Notification\NotificationController::class, 'markAllRead'])
            ->name('notifications.read-all');
        Route::get('/calendar/events', [\App\Http\Controllers\Notification\NotificationController::class, 'calendar'])
            ->name('calendar.events');
    });

    /* ------------------------------------------------- Pond Management ----- */
    /*
     | PHASE 2 — IMPLEMENTED. Every route is permission-gated AND the controller
     | re-asserts the policy, so an id in the URL cannot be used to reach a pond
     | the user is not permitted to act on (docs/PERMISSIONS.md §3).
     |
     | The literal `/types` and `/status` segments are declared BEFORE
     | `/{pond}` so they are never captured by the model-binding wildcard.
     */
    Route::prefix('fish-farm/ponds')->name('ponds.')->group(function () {

        /* ------------------------------------------------------ Pond types -- */
        Route::prefix('types')->name('types.')->group(function () {
            Route::middleware('permission:pond_type.view')->group(function () {
                Route::get('/', [PondTypeController::class, 'index'])->name('index');
            });

            Route::middleware('permission:pond_type.create')->group(function () {
                Route::get('/create', [PondTypeController::class, 'create'])->name('create');
                Route::post('/', [PondTypeController::class, 'store'])->name('store');
            });

            Route::middleware('permission:pond_type.update')->group(function () {
                Route::get('/{pondType}/edit', [PondTypeController::class, 'edit'])->name('edit');
                Route::put('/{pondType}', [PondTypeController::class, 'update'])->name('update');
            });

            Route::middleware('permission:pond_type.delete')->group(function () {
                Route::delete('/{pondType}', [PondTypeController::class, 'destroy'])->name('destroy');
            });
        });

        /* ----------------------------------------------------- Pond status -- */
        Route::middleware('permission:pond.view')->group(function () {
            Route::get('/status', PondStatusController::class)->name('status');
        });

        /* ----------------------------------------------------------- Ponds -- */
        Route::middleware('permission:pond.view')->group(function () {
            Route::get('/', [PondController::class, 'index'])->name('index');
            // Read-only GET that streams the (filtered) list as CSV.
            Route::get('/export', [PondController::class, 'export'])->name('export');
        });

        Route::middleware('permission:pond.create')->group(function () {
            Route::get('/create', [PondController::class, 'create'])->name('create');
            Route::post('/', [PondController::class, 'store'])->name('store');
        });

        Route::middleware('permission:pond.view')->group(function () {
            Route::get('/{pond}', [PondController::class, 'show'])->name('show');
        });

        Route::middleware('permission:pond.update')->group(function () {
            Route::get('/{pond}/edit', [PondController::class, 'edit'])->name('edit');
            Route::put('/{pond}', [PondController::class, 'update'])->name('update');
        });

        Route::middleware('permission:pond.delete')->group(function () {
            Route::delete('/{pond}', [PondController::class, 'destroy'])->name('destroy');
        });
    });

    /* ------------------------------------------------------ Pond Ledger ---- */
    /*
     | PHASE 5 — IMPLEMENTED. The Pond Ledger is a COMPLETE pond transaction /
     | history system. Its four sections are:
     |
     |   /fish-farm/pond-ledger              Pond Ledger (chronological timeline)
     |   /fish-farm/pond-ledger/stocking     Stocking (New Stock + Stock History)
     |   /fish-farm/pond-ledger/mortality    Death (Mortality)
     |   /fish-farm/pond-ledger/transfers    Transfers
     |
     | plus the money-entry views kept from the original ledger:
     |   /fish-farm/pond-ledger/transactions  money entries (income/expense)
     |
     | Every route is permission-gated AND the controller re-asserts the policy, so
     | an id in the URL cannot be used to reach a record the user is not permitted
     | to act on (docs/PERMISSIONS.md §3).
     |
     | Stocking and Mortality write the SAME tables as the Fish Stock module through
     | the SAME service — the ledger is the historical source of truth for pond
     | stock movement (docs/BUSINESS_LOGIC.md §2).
     */
    Route::prefix('fish-farm/pond-ledger')->name('ledger.')->group(function () {

        /* --------------------------------------- Ledger timeline (index) --- */
        Route::middleware('permission:pond_ledger.view')->group(function () {
            Route::get('/', PondLedgerController::class)->name('index');
        });

        /* -------------------------------------------------------- Stocking -- */
        Route::middleware('permission:pond_ledger.view')->group(function () {
            Route::get('/stocking', [StockingController::class, 'index'])->name('stocking');
        });

        Route::middleware('permission:pond_ledger.stocking.create')->group(function () {
            Route::post('/stocking', [StockingController::class, 'store'])->name('stocking.store');
        });

        /* ------------------------------------------------------- Mortality -- */
        Route::middleware('permission:pond_ledger.view')->group(function () {
            Route::get('/mortality', [MortalityController::class, 'index'])->name('mortality');
        });

        Route::middleware('permission:pond_ledger.mortality.create')->group(function () {
            Route::post('/mortality', [MortalityController::class, 'store'])->name('mortality.store');
        });

        /* ------------------------------------------------------- Transfers -- */
        Route::middleware('permission:pond_ledger.view')->group(function () {
            Route::get('/transfers', [TransferController::class, 'index'])->name('transfers');
        });

        Route::middleware('permission:pond_ledger.transfer.create')->group(function () {
            Route::post('/transfers', [TransferController::class, 'store'])->name('transfers.store');
        });

        Route::middleware('permission:pond_ledger.transfer.delete')->group(function () {
            Route::delete('/transfers/{transfer}', [TransferController::class, 'destroy'])->name('transfers.destroy');
        });

        /* ---------------------------------------------- Money transactions -- */
        Route::middleware('permission:ledger.view')->group(function () {
            Route::get('/transactions', [PondLedgerEntryController::class, 'index'])->name('transactions');
        });

        Route::middleware('permission:ledger.create')->group(function () {
            // Literal `/create` precedes `/{entry}` so it is never captured by
            // the model-binding wildcard.
            Route::get('/transactions/create', [PondLedgerEntryController::class, 'create'])->name('transactions.create');
            Route::post('/transactions', [PondLedgerEntryController::class, 'store'])->name('transactions.store');
        });

        Route::middleware('permission:ledger.delete')->group(function () {
            Route::delete('/transactions/{entry}', [PondLedgerEntryController::class, 'destroy'])->name('transactions.destroy');
        });
    });

    /* ------------------------------------------------ Food Management ------ */
    /*
     | PHASE 4 — IMPLEMENTED. Every route is permission-gated AND the controller
     | re-asserts the policy, so an id in the URL cannot be used to reach a record
     | the user is not permitted to act on (docs/PERMISSIONS.md §3).
     |
     | Literal segments (`types`, `stock`, `purchases`, `usages`, `adjustments`)
     | precede any `{wildcard}` so they are never captured by model binding.
     |
     | `feed.stock` reuses the dashboard controller — it is the same stock position
     | presented as the dedicated "Food Stock" page.
     */
    Route::prefix('fish-farm/feed')->name('feed.')->group(function () {

        /* -------------------------------------------------- Dashboard/Stock -- */
        Route::middleware('permission:feed.view')->group(function () {
            Route::get('/', FeedController::class)->name('index');
            Route::get('/stock', FeedController::class)->name('stock');
        });

        /* --------------------------------------------------- Feed types ----- */
        Route::middleware('permission:feed.view')->group(function () {
            Route::get('/types', [FeedTypeController::class, 'index'])->name('types.index');
        });

        Route::middleware('permission:feed.type.manage')->group(function () {
            Route::get('/types/create', [FeedTypeController::class, 'create'])->name('types.create');
            Route::post('/types', [FeedTypeController::class, 'store'])->name('types.store');
            Route::get('/types/{feedType}/edit', [FeedTypeController::class, 'edit'])->name('types.edit');
            Route::put('/types/{feedType}', [FeedTypeController::class, 'update'])->name('types.update');
            Route::delete('/types/{feedType}', [FeedTypeController::class, 'destroy'])->name('types.destroy');
        });

        /* ----------------------------------------------------- Purchases ---- */
        Route::middleware('permission:feed.view')->group(function () {
            Route::get('/purchases', [FeedPurchaseController::class, 'index'])->name('purchases.index');
            Route::get('/purchases/export', [FeedPurchaseController::class, 'export'])->name('purchases.export');
        });

        Route::middleware('permission:feed.purchase')->group(function () {
            Route::get('/purchases/create', [FeedPurchaseController::class, 'create'])->name('purchases.create');
            Route::post('/purchases', [FeedPurchaseController::class, 'store'])->name('purchases.store');
            Route::delete('/purchases/{purchase}', [FeedPurchaseController::class, 'destroy'])->name('purchases.destroy');
        });

        /* -------------------------------------------------------- Usages ---- */
        Route::middleware('permission:feed.view')->group(function () {
            Route::get('/usages', [FeedUsageController::class, 'index'])->name('usages.index');
            Route::get('/usages/export', [FeedUsageController::class, 'export'])->name('usages.export');
        });

        Route::middleware('permission:feed.usage')->group(function () {
            Route::get('/usages/create', [FeedUsageController::class, 'create'])->name('usages.create');
            Route::post('/usages', [FeedUsageController::class, 'store'])->name('usages.store');
            Route::delete('/usages/{usage}', [FeedUsageController::class, 'destroy'])->name('usages.destroy');
        });

        /* --------------------------------------------------- Adjustments ---- */
        Route::middleware('permission:feed.view')->group(function () {
            Route::get('/adjustments', [FeedAdjustmentController::class, 'index'])->name('adjustments.index');
        });

        Route::middleware('permission:feed.adjust')->group(function () {
            Route::get('/adjustments/create', [FeedAdjustmentController::class, 'create'])->name('adjustments.create');
            Route::post('/adjustments', [FeedAdjustmentController::class, 'store'])->name('adjustments.store');
            Route::delete('/adjustments/{adjustment}', [FeedAdjustmentController::class, 'destroy'])->name('adjustments.destroy');
        });

        /* ------------------------------------------- Feeding & Schedules ---- */
        /*
         | A meal schedule is a PLAN and never moves feed stock. Recording an
         | actual feeding writes a feed_usages row through FeedStockService, which
         | is the single authority for feed stock (docs/BUSINESS_LOGIC.md §2).
         |
         | Literal segments (`new`, `schedules`) precede any `{wildcard}`.
         */
        Route::middleware('permission:feed.view')->group(function () {
            Route::get('/feeding', [FeedingController::class, 'index'])->name('feedings.index');
            Route::get('/feeding/history', [FeedingController::class, 'index'])->name('feedings.history');
        });

        Route::middleware('permission:feed.schedule.manage')->group(function () {
            Route::get('/feeding/schedules', [FeedingController::class, 'schedules'])->name('schedules.index');
            Route::get('/feeding/schedules/create', [FeedingController::class, 'scheduleCreate'])->name('schedules.create');
            Route::post('/feeding/schedules', [FeedingController::class, 'scheduleStore'])->name('schedules.store');
            Route::get('/feeding/schedules/{schedule}/edit', [FeedingController::class, 'scheduleEdit'])->name('schedules.edit');
            Route::put('/feeding/schedules/{schedule}', [FeedingController::class, 'scheduleUpdate'])->name('schedules.update');
            Route::delete('/feeding/schedules/{schedule}', [FeedingController::class, 'scheduleDestroy'])->name('schedules.destroy');
        });

        Route::middleware('permission:feed.feeding')->group(function () {
            Route::get('/feeding/new', [FeedingController::class, 'feedingCreate'])->name('feedings.create');
            Route::post('/feeding', [FeedingController::class, 'feedingStore'])->name('feedings.store');
            Route::delete('/feeding/{feeding}', [FeedingController::class, 'feedingDestroy'])->name('feedings.destroy');
        });
    });

    /* ---------------------------------------------------- FCR & Growth ----- */
    /*
     | PHASE 6 — IMPLEMENTED. Every route is permission-gated AND the controller
     | re-asserts the policy, so an id in the URL cannot be used to reach a record
     | the user is not permitted to act on (docs/PERMISSIONS.md §3).
     |
     | Literal segments (`inspections`, `growth`, `schedules`) precede any
     | `{wildcard}` so they are never captured by model binding.
     |
     | `fcr.inspections.show` is declared after the literal `/inspections/new` and
     | `/inspections/create` so those are never captured by `/{inspection}`.
     */
    Route::prefix('fish-farm/fcr')->name('fcr.')->group(function () {

        /* ------------------------------------------------------ Dashboard -- */
        Route::middleware('permission:fcr.view')->group(function () {
            Route::get('/', FcrController::class)->name('index');
            Route::get('/feed-growth', FeedGrowthController::class)->name('feed-growth');
            Route::get('/comparison', ComparisonController::class)->name('comparison');
            Route::get('/reports', FcrReportController::class)->name('reports');
        });

        /* ---------------------------------------------------- Inspections -- */
        Route::middleware('permission:fcr.view')->group(function () {
            Route::get('/inspections', [InspectionController::class, 'index'])->name('inspections.index');
            Route::get('/inspections/new', [InspectionController::class, 'create'])->name('inspections.create');
            Route::get('/inspections/{inspection}', [InspectionController::class, 'show'])->name('inspections.show');
        });

        Route::middleware('permission:fcr.inspection.create')->group(function () {
            Route::post('/inspections', [InspectionController::class, 'store'])->name('inspections.store');
        });

        Route::middleware('permission:fcr.inspection.update')->group(function () {
            Route::get('/inspections/{inspection}/edit', [InspectionController::class, 'edit'])->name('inspections.edit');
            Route::put('/inspections/{inspection}', [InspectionController::class, 'update'])->name('inspections.update');
            Route::delete('/inspections/{inspection}', [InspectionController::class, 'destroy'])->name('inspections.destroy');
        });

        /* --------------------------------------------------------- Growth -- */
        Route::middleware('permission:growth.view')->group(function () {
            Route::get('/growth', [GrowthController::class, 'index'])->name('growth');
        });

        Route::middleware('permission:growth.create')->group(function () {
            Route::get('/growth/create', [GrowthController::class, 'create'])->name('growth.create');
            Route::post('/growth', [GrowthController::class, 'store'])->name('growth.store');
            Route::get('/growth/{growth}/edit', [GrowthController::class, 'edit'])->name('growth.edit');
            Route::put('/growth/{growth}', [GrowthController::class, 'update'])->name('growth.update');
            Route::delete('/growth/{growth}', [GrowthController::class, 'destroy'])->name('growth.destroy');
        });

        /* ------------------------------------------------------ Schedules -- */
        Route::middleware('permission:fcr.schedule.manage')->group(function () {
            Route::get('/schedules', [InspectionScheduleController::class, 'index'])->name('schedules.index');
            Route::post('/schedules', [InspectionScheduleController::class, 'store'])->name('schedules.store');
            Route::get('/schedules/{schedule}/edit', [InspectionScheduleController::class, 'edit'])->name('schedules.edit');
            Route::put('/schedules/{schedule}', [InspectionScheduleController::class, 'update'])->name('schedules.update');
            Route::delete('/schedules/{schedule}', [InspectionScheduleController::class, 'destroy'])->name('schedules.destroy');
        });
    });

    /* -------------------------------------------------------- Fish Stock --- */
    /*
     | PHASE 3 — IMPLEMENTED. Every route is permission-gated AND the controller
     | re-asserts the policy, so an id in the URL cannot be used to reach a record
     | the user is not permitted to act on (docs/PERMISSIONS.md §3).
     |
     | The literal segments (`species`, `stockings`, `mortalities`, `harvests`) are
     | declared BEFORE any `{wildcard}` so they are never captured by model binding.
     |
     | The write routes (create/store/destroy) carry the module's write permissions;
     | the dashboards and lists carry `fish.view`.
     */
    Route::prefix('fish-farm/fish')->name('fish.')->group(function () {

        /* ------------------------------------------------ Stock dashboard -- */
        Route::middleware('permission:fish.view')->group(function () {
            Route::get('/', FishStockController::class)->name('index');
        });

        /* ------------------------------------------------------- Species --- */
        Route::middleware('permission:fish.view')->group(function () {
            Route::get('/species', [FishSpeciesController::class, 'index'])->name('species.index');
        });

        Route::middleware('permission:fish.species.manage')->group(function () {
            Route::get('/species/create', [FishSpeciesController::class, 'create'])->name('species.create');
            Route::post('/species', [FishSpeciesController::class, 'store'])->name('species.store');
            Route::get('/species/{species}/edit', [FishSpeciesController::class, 'edit'])->name('species.edit');
            Route::put('/species/{species}', [FishSpeciesController::class, 'update'])->name('species.update');
            Route::delete('/species/{species}', [FishSpeciesController::class, 'destroy'])->name('species.destroy');
        });

        /* ------------------------------------------------------ Stockings -- */
        Route::middleware('permission:fish.view')->group(function () {
            Route::get('/stockings', [FishStockingController::class, 'index'])->name('stockings.index');
            Route::get('/stockings/export', [FishStockingController::class, 'export'])->name('stockings.export');
        });

        Route::middleware('permission:fish.stock')->group(function () {
            Route::get('/stockings/create', [FishStockingController::class, 'create'])->name('stockings.create');
            Route::post('/stockings', [FishStockingController::class, 'store'])->name('stockings.store');
            Route::delete('/stockings/{stocking}', [FishStockingController::class, 'destroy'])->name('stockings.destroy');
        });

        /* ---------------------------------------------------- Mortalities --- */
        Route::middleware('permission:fish.view')->group(function () {
            Route::get('/mortalities', [FishMortalityController::class, 'index'])->name('mortalities.index');
            Route::get('/mortalities/export', [FishMortalityController::class, 'export'])->name('mortalities.export');
        });

        Route::middleware('permission:fish.mortality')->group(function () {
            Route::get('/mortalities/create', [FishMortalityController::class, 'create'])->name('mortalities.create');
            Route::post('/mortalities', [FishMortalityController::class, 'store'])->name('mortalities.store');
            Route::delete('/mortalities/{mortality}', [FishMortalityController::class, 'destroy'])->name('mortalities.destroy');
        });

        /* -------------------------------------------------------- Harvest --- */
        Route::middleware('permission:fish.view')->group(function () {
            Route::get('/harvests', [HarvestController::class, 'index'])->name('harvests.index');
            Route::get('/harvests/export', [HarvestController::class, 'export'])->name('harvests.export');
        });

        Route::middleware('permission:fish.harvest')->group(function () {
            Route::get('/harvests/create', [HarvestController::class, 'create'])->name('harvests.create');
            Route::post('/harvests', [HarvestController::class, 'store'])->name('harvests.store');
            Route::delete('/harvests/{harvest}', [HarvestController::class, 'destroy'])->name('harvests.destroy');
        });

        /* -------------------------------------------------- Batches / Cycles -- */
        /*
         | A batch groups stocking/mortality/harvest records for one pond+species.
         | Its current quantity is DERIVED from those records (FishBatchService) —
         | the batch is never a second stock ledger. Literal `/create` precedes the
         | `{batch}` wildcard.
         */
        Route::prefix('batches')->name('batches.')->group(function () {
            Route::middleware('permission:fish.batch.view')->group(function () {
                Route::get('/', [FishBatchController::class, 'index'])->name('index');
            });

            Route::middleware('permission:fish.batch.manage')->group(function () {
                Route::get('/create', [FishBatchController::class, 'create'])->name('create');
                Route::post('/', [FishBatchController::class, 'store'])->name('store');
            });

            Route::middleware('permission:fish.batch.view')->group(function () {
                Route::get('/{batch}', [FishBatchController::class, 'show'])->name('show');
            });

            Route::middleware('permission:fish.batch.manage')->group(function () {
                Route::get('/{batch}/edit', [FishBatchController::class, 'edit'])->name('edit');
                Route::put('/{batch}', [FishBatchController::class, 'update'])->name('update');
                Route::delete('/{batch}', [FishBatchController::class, 'destroy'])->name('destroy');
            });
        });
    });

    /* --------------------------------------------- Sales & Accounts -------- */
    /*
     | PHASE 7 — IMPLEMENTED. Every route is permission-gated AND the controller
     | re-asserts the policy. `sales.ledger` keeps its documented name and is the
     | customer-dues view (what the farm is owed), which is what "sales ledger"
     | means on the farm.
     */
    Route::prefix('fish-farm/sales')->name('sales.')->group(function () {
        Route::middleware('permission:sales.view')->group(function () {
            Route::get('/', [SaleController::class, 'dashboard'])->name('index');
            Route::get('/list', [SaleController::class, 'index'])->name('list');
            Route::get('/export', [SaleController::class, 'export'])->name('export');
            // The sales ledger = what customers still owe.
            Route::get('/ledger', CustomerDueController::class)->name('ledger');
            Route::get('/ledger/export', [CustomerDueController::class, 'export'])->name('ledger.export');
        });

        /*
         | `/create` is registered BEFORE `/{sale}` on purpose: a static path must
         | win over the wildcard, otherwise `/create` is captured as `{sale}` and
         | 404s. (Same reason the inspection create route uses `/new` — see §above.)
         */
        Route::middleware('permission:sales.create')->group(function () {
            Route::get('/create', [SaleController::class, 'create'])->name('create');
            Route::post('/', [SaleController::class, 'store'])->name('store');
        });

        Route::middleware('permission:sales.view')->group(function () {
            Route::get('/{sale}', [SaleController::class, 'show'])->name('show');
        });

        Route::middleware('permission:sales.update')->group(function () {
            Route::get('/{sale}/edit', [SaleController::class, 'edit'])->name('edit');
            Route::put('/{sale}', [SaleController::class, 'update'])->name('update');
        });

        Route::middleware('permission:sales.delete')->group(function () {
            Route::delete('/{sale}', [SaleController::class, 'destroy'])->name('destroy');
        });
    });

    /* ----------------------------------------------------------- Customers -- */
    Route::prefix('fish-farm/customers')->name('customers.')->group(function () {
        Route::middleware('permission:customer.view')->group(function () {
            Route::get('/', [CustomerController::class, 'index'])->name('index');
            Route::get('/export', [CustomerController::class, 'export'])->name('export');
            Route::get('/dues', CustomerDueController::class)->name('dues');
            Route::get('/dues/export', [CustomerDueController::class, 'export'])->name('dues.export');
            Route::get('/payments', [CustomerPaymentController::class, 'index'])->name('payments.index');
            Route::get('/payments/export', [CustomerPaymentController::class, 'export'])->name('payments.export');
        });

        Route::middleware('permission:customer.create')->group(function () {
            Route::get('/create', [CustomerController::class, 'create'])->name('create');
            Route::post('/', [CustomerController::class, 'store'])->name('store');
        });

        Route::middleware('permission:customer.update')->group(function () {
            Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->name('edit');
            Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
        });

        Route::middleware('permission:customer.delete')->group(function () {
            Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
        });

        Route::middleware('permission:customer.payment.create')->group(function () {
            Route::get('/payments/create', [CustomerPaymentController::class, 'create'])->name('payments.create');
            Route::post('/payments', [CustomerPaymentController::class, 'store'])->name('payments.store');
            Route::delete('/payments/{payment}', [CustomerPaymentController::class, 'destroy'])->name('payments.destroy');
        });
    });

    /* --------------------------------------------------------- Suppliers --- */
    Route::prefix('fish-farm/suppliers')->name('suppliers.')->group(function () {
        Route::middleware('permission:supplier.view')->group(function () {
            Route::get('/', [SupplierController::class, 'index'])->name('index');
            Route::get('/export', [SupplierController::class, 'export'])->name('export');
            Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
            Route::get('/purchases/export', [PurchaseController::class, 'export'])->name('purchases.export');
            Route::get('/payments', [SupplierPaymentController::class, 'index'])->name('payments.index');
            Route::get('/payments/export', [SupplierPaymentController::class, 'export'])->name('payments.export');
            Route::get('/dues', SupplierDueController::class)->name('dues');
            Route::get('/dues/export', [SupplierDueController::class, 'export'])->name('dues.export');
        });

        Route::middleware('permission:supplier.create')->group(function () {
            Route::get('/create', [SupplierController::class, 'create'])->name('create');
            Route::post('/', [SupplierController::class, 'store'])->name('store');
        });

        Route::middleware('permission:supplier.update')->group(function () {
            Route::get('/{supplier}/edit', [SupplierController::class, 'edit'])->name('edit');
            Route::put('/{supplier}', [SupplierController::class, 'update'])->name('update');
        });

        Route::middleware('permission:supplier.delete')->group(function () {
            Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');
        });

        Route::middleware('permission:supplier.purchase.create')->group(function () {
            Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
            Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
            Route::get('/purchases/{purchase}/edit', [PurchaseController::class, 'edit'])->name('purchases.edit');
            Route::put('/purchases/{purchase}', [PurchaseController::class, 'update'])->name('purchases.update');
            Route::delete('/purchases/{purchase}', [PurchaseController::class, 'destroy'])->name('purchases.destroy');
        });

        Route::middleware('permission:supplier.payment.create')->group(function () {
            Route::get('/payments/create', [SupplierPaymentController::class, 'create'])->name('payments.create');
            Route::post('/payments', [SupplierPaymentController::class, 'store'])->name('payments.store');
            Route::delete('/payments/{payment}', [SupplierPaymentController::class, 'destroy'])->name('payments.destroy');
        });
    });

    /* ------------------------------------------------------------- Party --- */
    Route::prefix('fish-farm/parties')->name('parties.')->group(function () {
        Route::middleware('permission:party.view')->group(function () {
            Route::get('/', [PartyController::class, 'index'])->name('index');
            Route::get('/export', [PartyController::class, 'export'])->name('export');
            Route::get('/transactions', [PartyTransactionController::class, 'index'])->name('transactions');
            Route::get('/transactions/export', [PartyTransactionController::class, 'export'])->name('transactions.export');
            Route::get('/ledger', PartyLedgerController::class)->name('ledger');
            Route::get('/ledger/export', [PartyLedgerController::class, 'export'])->name('ledger.export');
        });

        Route::middleware('permission:party.create')->group(function () {
            Route::get('/create', [PartyController::class, 'create'])->name('create');
            Route::post('/', [PartyController::class, 'store'])->name('store');
        });

        Route::middleware('permission:party.update')->group(function () {
            Route::get('/{party}/edit', [PartyController::class, 'edit'])->name('edit');
            Route::put('/{party}', [PartyController::class, 'update'])->name('update');
        });

        Route::middleware('permission:party.delete')->group(function () {
            Route::delete('/{party}', [PartyController::class, 'destroy'])->name('destroy');
        });

        Route::middleware('permission:party.transaction.create')->group(function () {
            Route::get('/transactions/create', [PartyTransactionController::class, 'create'])->name('transactions.create');
            Route::post('/transactions', [PartyTransactionController::class, 'store'])->name('transactions.store');
            Route::delete('/transactions/{transaction}', [PartyTransactionController::class, 'destroy'])->name('transactions.destroy');
        });
    });

    /* ----------------------------------------------------------- Finance --- */
    Route::prefix('fish-farm/finance')->name('finance.')->group(function () {
        /* --------------------------------------------------------- Income -- */
        Route::middleware('permission:finance.view')->group(function () {
            Route::get('/income', [IncomeController::class, 'index'])->name('income.index');
            Route::get('/income/export', [IncomeController::class, 'export'])->name('income.export');
            Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
            Route::get('/expenses/export', [ExpenseController::class, 'export'])->name('expenses.export');
            Route::get('/categories', [ExpenseCategoryController::class, 'index'])->name('categories.index');
            Route::get('/profit-loss', [FinanceReportController::class, 'profitLoss'])->name('profit-loss');
            Route::get('/profit-loss/export', [FinanceReportController::class, 'export'])->name('profit-loss.export');
        });

        Route::middleware('permission:income.create')->group(function () {
            Route::get('/income/create', [IncomeController::class, 'create'])->name('income.create');
            Route::post('/income', [IncomeController::class, 'store'])->name('income.store');
        });

        Route::middleware('permission:income.delete')->group(function () {
            Route::delete('/income/{entry}', [IncomeController::class, 'destroy'])->name('income.destroy');
        });

        Route::middleware('permission:expense.create')->group(function () {
            Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
            Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        });

        Route::middleware('permission:expense.delete')->group(function () {
            Route::delete('/expenses/{entry}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
        });

        Route::middleware('permission:expense.category.manage')->group(function () {
            Route::get('/categories/create', [ExpenseCategoryController::class, 'create'])->name('categories.create');
            Route::post('/categories', [ExpenseCategoryController::class, 'store'])->name('categories.store');
            Route::get('/categories/{category}/edit', [ExpenseCategoryController::class, 'edit'])->name('categories.edit');
            Route::put('/categories/{category}', [ExpenseCategoryController::class, 'update'])->name('categories.update');
            Route::delete('/categories/{category}', [ExpenseCategoryController::class, 'destroy'])->name('categories.destroy');
        });
    });

    /* ----------------------------------------------------------- Reports --- */
    /*
     | PHASE 7 — IMPLEMENTED. Every report is a real query over existing data with
     | a filter form, a summary, a detailed table and a CSV export. A report that
     | needs a module that does not exist shows an honest empty state.
     */
    Route::prefix('fish-farm/reports')->name('reports.')->middleware('permission:reports.view')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/purchases', [ReportController::class, 'purchases'])->name('purchases');
        Route::get('/feed', [ReportController::class, 'feed'])->name('feed');
        Route::get('/fish-stock', [ReportController::class, 'fishStock'])->name('fish-stock');
        Route::get('/ponds', [ReportController::class, 'ponds'])->name('ponds');
        Route::get('/fcr-growth', [ReportController::class, 'fcrGrowth'])->name('fcr-growth');

        // Financial reports additionally require `reports.financial`, so income,
        // expense and profit can be withheld from a role that may only see
        // operational reports. The key was defined and granted but never enforced.
        Route::middleware('permission:reports.financial')->group(function () {
            Route::get('/income', [ReportController::class, 'income'])->name('income');
            Route::get('/expenses', [ReportController::class, 'expenses'])->name('expenses');
            Route::get('/profit-loss', [ReportController::class, 'profitLoss'])->name('profit-loss');
        });

        // The CSV export carries its OWN permission (`reports.export`), separately
        // from viewing a report — a role may be allowed to read a report on screen
        // without being allowed to download it.
        Route::get('/{report}/export', [ReportController::class, 'export'])
            ->middleware('permission:reports.export')
            ->name('export');
    });

    /* =====================================================================
     | Settings — Phase 1 IMPLEMENTED foundation
     | =====================================================================
     | Company, Users, Roles and Permissions are real, permission-gated pages.
     | The Profile page is intentionally protected by `auth` only: every user
     | may manage their own name/email/password, but never their own role.
     */
    Route::prefix('settings')->name('settings.')->group(function () {

        /* ------------------------------------------------------ Company ---- */
        Route::middleware('permission:company.view')->group(function () {
            Route::get('/company', [CompanyController::class, 'edit'])->name('company.edit');
        });

        Route::middleware('permission:company.update')->group(function () {
            Route::put('/company', [CompanyController::class, 'update'])->name('company.update');
        });

        /* ------------------------------------------------------- Profile --- */
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        /* --------------------------------------------------------- Users --- */
        Route::middleware('permission:users.view')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
        });

        Route::middleware('permission:users.create')->group(function () {
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
        });

        Route::middleware('permission:users.update')->group(function () {
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])
                ->name('users.toggle-active');
        });

        Route::middleware('permission:users.delete')->group(function () {
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });

        /* --------------------------------------------------------- Roles --- */
        Route::middleware('permission:roles.view')->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        });

        Route::middleware('permission:roles.create')->group(function () {
            Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
            Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
        });

        /*
         | Editing a role's DETAILS requires roles.update; the permission MATRIX
         | additionally requires permissions.manage, so granting capabilities is
         | not possible for someone who may only rename a role.
         */
        Route::middleware(['permission:roles.update', 'permission:permissions.manage'])->group(function () {
            Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        });

        Route::middleware('permission:roles.delete')->group(function () {
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        });

        /* --------------------------------------------------- Permissions --- */
        Route::middleware('permission:permissions.view')->group(function () {
            Route::get('/permissions', [PermissionController::class, 'index'])
                ->name('permissions.index');
        });
    });
});
