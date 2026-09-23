<?php

namespace App\Providers;

use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\ExpenseCategory;
use App\Models\ExpenseEntry;
use App\Models\FeedPurchase;
use App\Models\FeedStockAdjustment;
use App\Models\FeedType;
use App\Models\FeedUsage;
use App\Models\FishMortality;
use App\Models\FishSpecies;
use App\Models\FishStocking;
use App\Models\GrowthRecord;
use App\Models\Harvest;
use App\Models\Inspection;
use App\Models\InspectionSchedule;
use App\Models\IncomeEntry;
use App\Models\Party;
use App\Models\PartyTransaction;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Policies\IncomeEntryPolicy;
use App\Policies\PartyPolicy;
use App\Policies\PartyTransactionPolicy;
use App\Policies\PurchasePolicy;
use App\Policies\SalePolicy;
use App\Policies\SupplierPaymentPolicy;
use App\Policies\SupplierPolicy;
use App\Models\Pond;
use App\Models\PondLedgerEntry;
use App\Models\PondTransfer;
use App\Models\PondType;
use App\Models\User;
use App\Policies\CustomerPaymentPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\ExpenseCategoryPolicy;
use App\Policies\ExpenseEntryPolicy;
use App\Policies\FeedPurchasePolicy;
use App\Policies\FeedStockAdjustmentPolicy;
use App\Policies\FeedTypePolicy;
use App\Policies\FeedUsagePolicy;
use App\Policies\FishMortalityPolicy;
use App\Policies\FishSpeciesPolicy;
use App\Policies\FishStockingPolicy;
use App\Policies\GrowthRecordPolicy;
use App\Policies\HarvestPolicy;
use App\Policies\InspectionPolicy;
use App\Policies\InspectionSchedulePolicy;
use App\Policies\PondLedgerEntryPolicy;
use App\Policies\PondPolicy;
use App\Policies\PondTransferPolicy;
use App\Policies\PondTypePolicy;
use App\View\Composers\CompanyComposer;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerGateBypass();
        $this->registerPolicies();
        $this->registerBladeDirectives();
        $this->registerViewComposers();
        $this->registerNotificationObservers();
    }

    /**
     * Emit a notification when a real ERP record is created (sale, purchase,
     * stocking, payment, …). Model events keep the business services untouched.
     */
    private function registerNotificationObservers(): void
    {
        \App\Models\Sale::observe(\App\Observers\SaleObserver::class);
        \App\Models\Purchase::observe(\App\Observers\PurchaseObserver::class);

        // Fish + finance activity all map to one observer with named methods.
        $fish = \App\Observers\FishActivityObserver::class;
        $listen = function (string $model, string $method) use ($fish): void {
            \Illuminate\Support\Facades\Event::listen(
                'eloquent.created: ' . $model,
                fn ($modelInstance) => app($fish)->{$method}($modelInstance),
            );
        };

        $listen(\App\Models\FishStocking::class, 'stocking');
        $listen(\App\Models\FishMortality::class, 'mortality');
        $listen(\App\Models\Harvest::class, 'harvest');
        $listen(\App\Models\Inspection::class, 'inspection');
        $listen(\App\Models\GrowthRecord::class, 'growth');
        $listen(\App\Models\IncomeEntry::class, 'income');
        $listen(\App\Models\ExpenseEntry::class, 'expense');
        $listen(\App\Models\CustomerPayment::class, 'customerPayment');
    }

    /**
     * Per-record authorization for the business models.
     *
     * Laravel would discover these by naming convention; registering them
     * explicitly keeps the policy map visible in one place as modules land.
     * See docs/PERMISSIONS.md §7.
     */
    private function registerPolicies(): void
    {
        Gate::policy(Pond::class, PondPolicy::class);
        Gate::policy(PondType::class, PondTypePolicy::class);

        // Phase 3 — Fish Stock
        Gate::policy(FishSpecies::class, FishSpeciesPolicy::class);
        Gate::policy(FishStocking::class, FishStockingPolicy::class);
        Gate::policy(FishMortality::class, FishMortalityPolicy::class);
        Gate::policy(Harvest::class, HarvestPolicy::class);

        // Fish batches / stocking cycles
        Gate::policy(\App\Models\FishBatch::class, \App\Policies\FishBatchPolicy::class);

        // Phase 4 — Feed (Food) Management
        Gate::policy(FeedType::class, FeedTypePolicy::class);
        Gate::policy(FeedPurchase::class, FeedPurchasePolicy::class);
        Gate::policy(FeedUsage::class, FeedUsagePolicy::class);
        Gate::policy(FeedStockAdjustment::class, FeedStockAdjustmentPolicy::class);

        // Feeding schedules (plans) and feedings (actual meals)
        Gate::policy(\App\Models\FeedingSchedule::class, \App\Policies\FeedingSchedulePolicy::class);
        Gate::policy(\App\Models\Feeding::class, \App\Policies\FeedingPolicy::class);

        // Phase 5 — Pond Ledger
        Gate::policy(PondLedgerEntry::class, PondLedgerEntryPolicy::class);
        Gate::policy(PondTransfer::class, PondTransferPolicy::class);

        // Phase 6 — FCR & Growth
        Gate::policy(GrowthRecord::class, GrowthRecordPolicy::class);
        Gate::policy(Inspection::class, InspectionPolicy::class);
        Gate::policy(InspectionSchedule::class, InspectionSchedulePolicy::class);

        // Phase 7 — Sales, Customers, Suppliers, Parties, Finance
        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(CustomerPayment::class, CustomerPaymentPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Purchase::class, PurchasePolicy::class);
        Gate::policy(SupplierPayment::class, SupplierPaymentPolicy::class);
        Gate::policy(Party::class, PartyPolicy::class);
        Gate::policy(PartyTransaction::class, PartyTransactionPolicy::class);
        Gate::policy(IncomeEntry::class, IncomeEntryPolicy::class);
        Gate::policy(ExpenseEntry::class, ExpenseEntryPolicy::class);
        Gate::policy(ExpenseCategory::class, ExpenseCategoryPolicy::class);
    }

    /**
     * Share company branding with the layouts so the company name/logo is never
     * hard-coded. Applies to the layout + auth layout components.
     */
    private function registerViewComposers(): void
    {
        View::composer([
            'components.layout.auth',
            'components.layout.error',
            'auth.login',
        ], CompanyComposer::class);
    }

    /**
     * Super Admin bypass — the ONLY bypass in the system.
     *
     * A Super Admin holds every permission (`*` is expanded by RoleSeeder), but
     * this guarantees a newly added permission is never accidentally denied to
     * them. Every other decision goes through a real permission key.
     *
     * See docs/PERMISSIONS.md §7.
     */
    private function registerGateBypass(): void
    {
        Gate::before(function (User $user, string $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
    }

    /**
     * Blade directives for permission-aware markup.
     *
     * These control DISPLAY ONLY. They never replace route middleware or policy
     * checks — see docs/PERMISSIONS.md. Backed by the once-per-request
     * permission cache on the User model, so repeated checks are cheap.
     *
     *   @permission('users.create') ... @endpermission
     *   @role('super_admin') ... @endrole
     */
    private function registerBladeDirectives(): void
    {
        Blade::if('permission', function (string ...$permissions): bool {
            $user = auth()->user();

            if (! $user) {
                return false;
            }

            foreach ($permissions as $permission) {
                if (! $user->hasPermission($permission)) {
                    return false;
                }
            }

            return true;
        });

        Blade::if('role', function (string ...$roles): bool {
            $user = auth()->user();

            return $user !== null && $user->hasAnyRole($roles);
        });
    }
}
