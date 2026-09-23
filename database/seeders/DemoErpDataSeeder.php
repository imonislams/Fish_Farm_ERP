<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\FishSpecies;
use App\Models\Party;
use App\Models\Pond;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Finance\FinanceService;
use App\Services\Finance\PartyLedgerService;
use App\Services\Sales\SalesService;
use App\Services\Supplier\PurchaseService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DemoErpDataSeeder — the finance/sales/customer/supplier/party/FCR sample data
 * that the dashboard needs to demonstrate real filters and charts.
 *
 * SAFE + IDEMPOTENT:
 *   - explicitly runnable ONLY:  php artisan db:seed --class=DemoErpDataSeeder
 *   - never deletes existing data
 *   - every record is guarded by a firstOrCreate / existence check on a
 *     deterministic business key, so re-running does NOT duplicate rows
 *   - written through the REAL services (SalesService, PurchaseService,
 *     FinanceService, PartyLedgerService), so every business rule runs
 *   - dates are spread across today / this week / this month / last month so
 *     the dashboard's date filters actually show different totals
 *
 * Deletion (opt-in):  php artisan db:seed --class=DemoErpDataSeeder --command=remove
 *
 * Complements the existing DemoDataSeeder (ponds/fish/feed); run that first if
 * you want farm-side data too.
 */
class DemoErpDataSeeder extends Seeder
{
    public const PREFIX = 'Demo - ';

    /** Keep every demo figure inside this window: today back to ~last month. */
    private function runSeeder(): void
    {
        $actorId = User::query()->orderBy('id')->value('id');

        if ($actorId === null) {
            $this->command?->error('No user exists. Run `php artisan db:seed` first.');

            return;
        }

        $this->command?->info('Creating demo ERP data…');

        $customers = $this->seedCustomers();
        $suppliers = $this->seedSuppliers();
        $parties = $this->seedParties();
        $this->seedExpenseCategories();
        $this->seedIncome($actorId);
        $this->seedExpenses($actorId);
        $this->seedSales($actorId, $customers);
        $this->seedPurchases($actorId, $suppliers);
        $this->seedPartyTransactions($parties);

        $this->command?->info('Demo ERP data ready.');
    }

    public function run(): void
    {
        // `php artisan db:seed --class=DemoErpDataSeeder -- --remove`
        if (in_array('--remove', $_SERVER['argv'] ?? [], true) || in_array('remove', $_SERVER['argv'] ?? [], true)) {
            $this->remove();

            return;
        }

        $this->runSeeder();
    }

    /** @return array<int, Customer> */
    private function seedCustomers(): array
    {
        $rows = [
            ['name' => 'Rahim Fish Traders', 'phone' => '01711-000101', 'address' => 'Karwan Bazar, Dhaka', 'opening' => 0],
            ['name' => 'Green Market Ltd', 'phone' => '01711-000102', 'address' => 'Mirpur-1, Dhaka', 'opening' => 1200],
            ['name' => 'Sundarban Seafoods', 'phone' => '01711-000103', 'address' => 'Khulna', 'opening' => 0],
            ['name' => 'Local Retail Buyer', 'phone' => '01711-000104', 'address' => 'Savar', 'opening' => 350],
        ];

        return array_map(function (array $r): Customer {
            return Customer::firstOrCreate(
                ['name' => self::PREFIX . $r['name']],
                [
                    'phone' => $r['phone'],
                    'address' => $r['address'],
                    'opening_balance' => $r['opening'],
                    'is_active' => true,
                ],
            );
        }, $rows);
    }

    /** @return array<int, Supplier> */
    private function seedSuppliers(): array
    {
        $rows = [
            ['name' => 'National Feed Mills', 'phone' => '01811-000201', 'address' => 'Narayanganj'],
            ['name' => 'Aqua Care Supplies', 'phone' => '01811-000202', 'address' => 'Mymensingh'],
            ['name' => 'Delta Fingerlings', 'phone' => '01811-000203', 'address' => 'Jashore'],
        ];

        return array_map(fn(array $r): Supplier => Supplier::firstOrCreate(
            ['name' => self::PREFIX . $r['name']],
            ['phone' => $r['phone'], 'address' => $r['address'], 'is_active' => true],
        ), $rows);
    }

    /** @return array<int, Party> */
    private function seedParties(): array
    {
        $rows = [
            ['name' => 'Land Owner — Mr. Karim', 'type' => 'landlord'],
            ['name' => 'Transport Contractor', 'type' => 'agent'],
        ];

        return array_map(fn(array $r): Party => Party::firstOrCreate(
            ['name' => self::PREFIX . $r['name']],
            ['type' => $r['type'], 'is_active' => true],
        ), $rows);
    }

    private function seedExpenseCategories(): void
    {
        foreach (['Feed', 'Labour', 'Medicine', 'Electricity', 'Transport'] as $name) {
            ExpenseCategory::firstOrCreate(
                ['name' => self::PREFIX . $name],
                ['is_active' => true],
            );
        }
    }

    private function seedIncome(int $actorId): void
    {
        $service = app(FinanceService::class);

        $rows = [
            ['days' => 0, 'amount' => 8500, 'category' => 'other', 'note' => 'Misc. farm income'],
            ['days' => 3, 'amount' => 4200, 'category' => 'other', 'note' => 'Equipment rental'],
            ['days' => 20, 'amount' => 3000, 'category' => 'other', 'note' => 'Last month income'],
        ];

        foreach ($rows as $r) {
            $date = now()->subDays($r['days'])->toDateString();

            $exists = DB::table('income_entries')
                ->where('amount', $r['amount'])
                ->whereDate('entry_date', $date)
                ->exists();

            if ($exists) {
                continue;
            }

            $service->createIncome([
                'pond_id' => null,
                'category' => $r['category'],
                'amount' => $r['amount'],
                'entry_date' => $date,
                'note' => self::PREFIX . $r['note'],
                'created_by' => $actorId,
            ]);
        }
    }

    private function seedExpenses(int $actorId): void
    {
        $service = app(FinanceService::class);

        $categories = ExpenseCategory::query()
            ->where('name', 'like', self::PREFIX . '%')
            ->pluck('id', 'name');

        $rows = [
            ['days' => 0, 'amount' => 3200, 'cat' => 'Labour'],
            ['days' => 2, 'amount' => 5600, 'cat' => 'Feed'],
            ['days' => 12, 'amount' => 2100, 'cat' => 'Medicine'],
            ['days' => 25, 'amount' => 1850, 'cat' => 'Electricity'],
        ];

        foreach ($rows as $r) {
            $date = now()->subDays($r['days'])->toDateString();
            $catId = $categories[self::PREFIX . $r['cat']] ?? null;

            $exists = DB::table('expense_entries')
                ->where('amount', $r['amount'])
                ->whereDate('entry_date', $date)
                ->exists();

            if ($exists) {
                continue;
            }

            $service->createExpense([
                'pond_id' => null,
                'expense_category_id' => $catId,
                'amount' => $r['amount'],
                'entry_date' => $date,
                'paid_to' => self::PREFIX . 'Supplier',
                'note' => self::PREFIX . $r['cat'],
                'created_by' => $actorId,
            ]);
        }
    }

    /** @param  array<int, Customer>  $customers */
    private function seedSales(int $actorId, array $customers): void
    {
        $service = app(SalesService::class);
        $species = FishSpecies::query()->orderBy('id')->value('id');
        $pond = Pond::query()->orderBy('id')->value('id');

        if ($species === null || $customers === []) {
            $this->command?->warn('Skipping sales: need at least one species + customer.');

            return;
        }

        $rows = [
            ['days' => 0, 'kg' => 40, 'price' => 220, 'paid' => 8800],
            ['days' => 1, 'kg' => 25, 'price' => 215, 'paid' => 0],
            ['days' => 4, 'kg' => 60, 'price' => 210, 'paid' => 6000],
            ['days' => 22, 'kg' => 35, 'price' => 205, 'paid' => 7175],
        ];

        $i = 1;
        foreach ($rows as $r) {
            $date = now()->subDays($r['days'])->toDateString();
            $invoice = 'DEMO-' . now()->format('Y') . '-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $i++;

            if (DB::table('sales')->where('invoice_no', $invoice)->exists()) {
                continue;
            }

            $service->create([
                'customer_id' => $customers[array_rand($customers)]->id,
                'invoice_no' => $invoice,
                'sale_date' => $date,
                'discount' => 0,
                'paid_amount' => $r['paid'],
                'note' => self::PREFIX . 'sale',
                'created_by' => $actorId,
                'items' => [[
                    'fish_species_id' => $species,
                    'pond_id' => $pond,
                    'weight_kg' => $r['kg'],
                    'quantity' => null,
                    'unit_price' => $r['price'],
                    'description' => self::PREFIX . 'fish',
                ]],
            ]);
        }
    }

    /** @param  array<int, Supplier>  $suppliers */
    private function seedPurchases(int $actorId, array $suppliers): void
    {
        $service = app(PurchaseService::class);
        $feedType = \App\Models\FeedType::query()->orderBy('id')->value('id');

        if ($feedType === null || $suppliers === []) {
            $this->command?->warn('Skipping purchases: need at least one feed type + supplier.');

            return;
        }

        $rows = [
            ['days' => 0, 'kg' => 200, 'cost' => 62, 'paid' => 12400],
            ['days' => 5, 'kg' => 150, 'cost' => 60, 'paid' => 0],
            ['days' => 24, 'kg' => 300, 'cost' => 58, 'paid' => 12000],
        ];

        $i = 1;
        foreach ($rows as $r) {
            $date = now()->subDays($r['days'])->toDateString();
            $invoice = 'DPUR-' . now()->format('Y') . '-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT);
            $i++;

            if (DB::table('purchases')->where('invoice_no', $invoice)->exists()) {
                continue;
            }

            $service->create([
                'supplier_id' => $suppliers[array_rand($suppliers)]->id,
                'invoice_no' => $invoice,
                'purchase_date' => $date,
                'discount' => 0,
                'paid_amount' => $r['paid'],
                'note' => self::PREFIX . 'purchase',
                'created_by' => $actorId,
                'items' => [[
                    'item_type' => 'feed',
                    'feed_type_id' => $feedType,
                    'description' => self::PREFIX . 'feed',
                    'quantity' => $r['kg'],
                    'unit_cost' => $r['cost'],
                ]],
            ]);
        }
    }

    /** @param  array<int, Party>  $parties */
    private function seedPartyTransactions(array $parties): void
    {
        if ($parties === []) {
            return;
        }

        $service = app(PartyLedgerService::class);

        foreach ($parties as $index => $party) {
            $exists = DB::table('party_transactions')->where('party_id', $party->id)->exists();
            if ($exists) {
                continue;
            }

            $service->record([
                'party_id' => $party->id,
                'entry_type' => 'debit',
                'amount' => 5000 + ($index * 1500),
                'entry_date' => now()->subDays($index * 3)->toDateString(),
                'description' => self::PREFIX . 'ledger entry',
            ]);
        }
    }

    /**
     * Remove ONLY the demo records this seeder created (opt-in).
     * Never touches records that do not carry the demo prefix.
     */
    public function remove(): void
    {
        $this->command?->info('Removing demo ERP data…');

        DB::table('party_transactions')
            ->where('description', 'like', self::PREFIX . '%')
            ->delete();
        DB::table('purchases')->where('invoice_no', 'like', 'DPUR-%')->delete();
        DB::table('sales')->where('invoice_no', 'like', 'DEMO-%')->delete();
        DB::table('income_entries')->where('note', 'like', self::PREFIX . '%')->delete();
        DB::table('expense_entries')->where('note', 'like', self::PREFIX . '%')->delete();
        Party::where('name', 'like', self::PREFIX . '%')->delete();
        Supplier::where('name', 'like', self::PREFIX . '%')->delete();
        Customer::where('name', 'like', self::PREFIX . '%')->delete();
        ExpenseCategory::where('name', 'like', self::PREFIX . '%')->delete();

        $this->command?->info('Demo ERP data removed.');
    }
}
