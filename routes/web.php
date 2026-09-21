<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Settings\CompanyController;
use App\Http\Controllers\Settings\PendingController;
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
|   - Every route MUST have a name. Blade uses route() names, never raw URLs.
|   - Route names follow the module prefix (ponds.index, ponds.create, …).
|   - Authorization middleware/policies attach per group as modules land.
|   - Modules that are not implemented yet resolve to PendingController, which
|     states so honestly — nothing in this file fakes data.
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

    /* ------------------------------------------------- Pond Management ----- */
    Route::prefix('fish-farm/ponds')->name('ponds.')->group(function () {
        Route::get('/', PendingController::class)->name('index');
        Route::get('/create', PendingController::class)->name('create');
        Route::get('/types', PendingController::class)->name('types.index');
        Route::get('/status', PendingController::class)->name('status');
    });

    /* ------------------------------------------------------ Pond Ledger ---- */
    Route::prefix('fish-farm/pond-ledger')->name('ledger.')->group(function () {
        Route::get('/', PendingController::class)->name('index');
        Route::get('/transactions', PendingController::class)->name('transactions');
    });

    /* ------------------------------------------------ Food Management ------ */
    Route::prefix('fish-farm/feed')->name('feed.')->group(function () {
        Route::get('/', PendingController::class)->name('index');
        Route::get('/types', PendingController::class)->name('types.index');
        Route::get('/stock', PendingController::class)->name('stock');
        Route::get('/purchases', PendingController::class)->name('purchases.index');
        Route::get('/usages', PendingController::class)->name('usages.index');
    });

    /* ---------------------------------------------------- FCR & Growth ----- */
    Route::prefix('fish-farm/fcr')->name('fcr.')->group(function () {
        Route::get('/', PendingController::class)->name('index');
        Route::get('/inspections', PendingController::class)->name('inspections.index');
        Route::get('/inspections/new', PendingController::class)->name('inspections.create');
        Route::get('/growth', PendingController::class)->name('growth');
        Route::get('/feed-growth', PendingController::class)->name('feed-growth');
        Route::get('/comparison', PendingController::class)->name('comparison');
        Route::get('/schedules', PendingController::class)->name('schedules.index');
        Route::get('/reports', PendingController::class)->name('reports');
    });

    /* -------------------------------------------------------- Fish Stock --- */
    Route::prefix('fish-farm/fish')->name('fish.')->group(function () {
        Route::get('/', PendingController::class)->name('index');
        Route::get('/species', PendingController::class)->name('species.index');
        Route::get('/stockings', PendingController::class)->name('stockings.index');
        Route::get('/mortalities', PendingController::class)->name('mortalities.index');
        Route::get('/harvests', PendingController::class)->name('harvests.index');
    });

    /* --------------------------------------------- Sales & Accounts -------- */
    Route::prefix('fish-farm/sales')->name('sales.')->group(function () {
        Route::get('/', PendingController::class)->name('index');
        Route::get('/list', PendingController::class)->name('list');
        Route::get('/ledger', PendingController::class)->name('ledger');
    });

    Route::prefix('fish-farm/customers')->name('customers.')->group(function () {
        Route::get('/', PendingController::class)->name('index');
        Route::get('/payments', PendingController::class)->name('payments.index');
        Route::get('/dues', PendingController::class)->name('dues');
    });

    /* --------------------------------------------------------- Suppliers --- */
    Route::prefix('fish-farm/suppliers')->name('suppliers.')->group(function () {
        Route::get('/', PendingController::class)->name('index');
        Route::get('/create', PendingController::class)->name('create');
        Route::get('/purchases', PendingController::class)->name('purchases.index');
        Route::get('/payments', PendingController::class)->name('payments.index');
        Route::get('/dues', PendingController::class)->name('dues');
    });

    /* ------------------------------------------------------------- Party --- */
    Route::prefix('fish-farm/parties')->name('parties.')->group(function () {
        Route::get('/', PendingController::class)->name('index');
        Route::get('/create', PendingController::class)->name('create');
        Route::get('/transactions', PendingController::class)->name('transactions');
        Route::get('/ledger', PendingController::class)->name('ledger');
    });

    /* ----------------------------------------------------------- Finance --- */
    Route::prefix('fish-farm/finance')->name('finance.')->group(function () {
        Route::get('/income', PendingController::class)->name('income.index');
        Route::get('/expenses', PendingController::class)->name('expenses.index');
    });

    /* ----------------------------------------------------------- Reports --- */
    Route::prefix('fish-farm/reports')->name('reports.')->group(function () {
        Route::get('/sales', PendingController::class)->name('sales');
        Route::get('/purchases', PendingController::class)->name('purchases');
        Route::get('/feed', PendingController::class)->name('feed');
        Route::get('/fish-stock', PendingController::class)->name('fish-stock');
        Route::get('/ponds', PendingController::class)->name('ponds');
        Route::get('/fcr-growth', PendingController::class)->name('fcr-growth');
        Route::get('/income', PendingController::class)->name('income');
        Route::get('/expenses', PendingController::class)->name('expenses');
        Route::get('/profit-loss', PendingController::class)->name('profit-loss');
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
