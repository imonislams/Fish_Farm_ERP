<?php

namespace Database\Seeders;

use App\Models\FeedType;
use App\Models\FishSpecies;
use App\Models\Pond;
use App\Models\PondType;
use App\Models\User;
use App\Services\Feed\FeedStockService;
use App\Services\Fish\FishStockService;
use App\Services\Pond\PondLedgerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * DemoDataSeeder — SAMPLE BUSINESS DATA for manual UI checking.
 *
 * ⚠️  THIS CREATES FAKE BUSINESS DATA.
 *
 * It is DELIBERATELY NOT called by DatabaseSeeder, so `php artisan db:seed`
 * never creates fake records (docs/PROJECT.md §12). Run it explicitly and only
 * on a database you are happy to fill with sample data:
 *
 *     php artisan db:seed --class=DemoDataSeeder
 *
 * To remove everything it created:
 *
 *     php artisan db:seed --class=DemoDataSeeder --command=remove
 *     # or simply: php artisan tinker  →  (new DemoDataSeeder)->remove()
 *
 * Everything is written through the REAL services (FishStockService,
 * FeedStockService, PondLedgerService), so every business rule runs exactly as
 * it would from the UI — the sample data can never be in a state the app forbids.
 *
 * The names are all prefixed so this data is obvious and easy to find/remove.
 */
class DemoDataSeeder extends Seeder
{
    public const PREFIX = 'Demo — ';

    public function run(): void
    {
        $actor = User::query()->orderBy('id')->value('id');

        if ($actor === null) {
            $this->command?->error('No user exists. Run `php artisan db:seed` first (creates the admin).');

            return;
        }

        $this->command?->info('Creating demo data…');

        // --- Pond types ------------------------------------------------------
        $growOut = PondType::firstOrCreate(
            ['name' => self::PREFIX . 'Grow Out Pond'],
            ['description' => 'Ponds for growing fish to market size.', 'is_active' => true],
        );
        $nursery = PondType::firstOrCreate(
            ['name' => self::PREFIX . 'Nursery Pond'],
            ['description' => 'Small ponds for rearing fry and fingerlings.', 'is_active' => true],
        );

        // --- Ponds -----------------------------------------------------------
        $ponds = [];
        $pondSpecs = [
            ['P-01', 'Main Grow Out', $growOut, '25.000', 'decimal', '1.800', 'metre', 'North block', 'Canal', 'active'],
            ['P-02', 'North Grow Out', $growOut, '18.500', 'decimal', '1.600', 'metre', 'North block', 'Tube well', 'active'],
            ['P-03', 'Hospital Pond', $growOut, '6.000', 'decimal', '1.200', 'metre', 'South block', 'Canal', 'maintenance'],
            ['N-01', 'Nursery One', $nursery, '2.500', 'decimal', '0.900', 'metre', 'Near house', 'Tube well', 'active'],
            ['N-02', 'Nursery Two', $nursery, '2.000', 'decimal', '0.850', 'metre', 'Near house', 'Tube well', 'empty'],
        ];

        foreach ($pondSpecs as [$number, $name, $type, $size, $sizeUnit, $depth, $depthUnit, $location, $source, $status]) {
            $ponds[$number] = Pond::updateOrCreate(
                ['pond_number' => $number],
                [
                    'name' => self::PREFIX . $name,
                    'pond_type_id' => $type->id,
                    'size' => $size,
                    'size_unit' => $sizeUnit,
                    'depth' => $depth,
                    'depth_unit' => $depthUnit,
                    'location' => $location,
                    'water_source' => $source,
                    'status' => $status,
                    'is_active' => in_array($status, ['active', 'empty'], true),
                    'description' => 'Sample pond created by DemoDataSeeder.',
                ],
            );
        }

        // --- Fish species ----------------------------------------------------
        $species = [];
        foreach (
            [
                ['Rohu', 'রুই', 'Labeo rohita', '250.00'],
                ['Tilapia', 'তেলাপিয়া', 'Oreochromis niloticus', '180.00'],
                ['Catla', 'কাতলা', 'Catla catla', '300.00'],
            ] as [$name, $local, $scientific, $price]
        ) {
            $species[$name] = FishSpecies::updateOrCreate(
                ['name' => self::PREFIX . $name],
                [
                    'local_name' => $local,
                    'scientific_name' => $scientific,
                    'default_price_per_kg' => $price,
                    'is_active' => true,
                    'description' => 'Sample species created by DemoDataSeeder.',
                ],
            );
        }

        // --- Feed types ------------------------------------------------------
        $feedTypes = [];
        foreach (
            [
                ['Starter Feed', 'Quality Feed', '32.00', '50 kg bag', '50.000', '2200.00', '40.000'],
                ['Grower Pellet', 'Quality Feed', '28.00', '50 kg bag', '50.000', '1950.00', '80.000'],
                ['Finisher Feed', 'AquaFeed', '26.00', '40 kg bag', '40.000', '1800.00', '60.000'],
            ] as [$name, $brand, $protein, $unit, $packageKg, $cost, $lowLevel]
        ) {
            $feedTypes[$name] = FeedType::updateOrCreate(
                ['name' => self::PREFIX . $name],
                [
                    'brand' => $brand,
                    'protein_percent' => $protein,
                    'unit' => $unit,
                    'package_weight_kg' => $packageKg,
                    'default_unit_cost' => $cost,
                    'low_stock_level_kg' => $lowLevel,
                    'is_active' => true,
                    'description' => 'Sample feed created by DemoDataSeeder.',
                ],
            );
        }

        // --- Movements, written through the REAL services --------------------
        $fish = app(FishStockService::class);
        $feed = app(FeedStockService::class);
        $ledger = app(PondLedgerService::class);

        $today = now()->toDateString();

        // Stockings (only if none exist for the demo ponds, so re-running is safe).
        if ($ponds['P-01']->stockings()->count() === 0) {
            $fish->recordStocking([
                'pond_id' => $ponds['P-01']->id,
                'fish_species_id' => $species['Rohu']->id,
                'quantity' => 4000,
                'avg_weight_g' => 25,
                'unit_cost' => 9,
                'stocked_on' => now()->subDays(90)->toDateString(),
                'supplier_name' => 'Demo Hatchery',
                'note' => 'Sample stocking.',
                'created_by' => $actor,
            ]);

            $fish->recordStocking([
                'pond_id' => $ponds['P-02']->id,
                'fish_species_id' => $species['Tilapia']->id,
                'quantity' => 3000,
                'avg_weight_g' => 20,
                'unit_cost' => 7,
                'stocked_on' => now()->subDays(75)->toDateString(),
                'supplier_name' => 'Demo Hatchery',
                'created_by' => $actor,
            ]);

            $fish->recordStocking([
                'pond_id' => $ponds['N-01']->id,
                'fish_species_id' => $species['Catla']->id,
                'quantity' => 2500,
                'avg_weight_g' => 8,
                'unit_cost' => 4,
                'stocked_on' => now()->subDays(45)->toDateString(),
                'created_by' => $actor,
            ]);
        }

        // Mortality + harvest + a transfer (guarded by existing rows).
        if ($ponds['P-01']->mortalities()->count() === 0) {
            $fish->recordMortality([
                'pond_id' => $ponds['P-01']->id,
                'quantity' => 60,
                'avg_weight_g' => 30,
                'recorded_on' => now()->subDays(40)->toDateString(),
                'cause' => 'disease',
                'note' => 'Sample mortality.',
                'created_by' => $actor,
            ]);
        }

        if ($ponds['P-01']->harvests()->count() === 0) {
            $fish->recordHarvest([
                'pond_id' => $ponds['P-01']->id,
                'fish_species_id' => $species['Rohu']->id,
                'quantity' => 800,
                'total_weight_kg' => 640.5,
                'harvested_on' => now()->subDays(20)->toDateString(),
                'destination' => 'Local market',
                'created_by' => $actor,
            ]);
        }

        if (
            \App\Models\PondTransfer::query()->count() === 0
            && $fish->currentStock($ponds['N-01']) >= 500
        ) {
            $fish->recordTransfer([
                'from_pond_id' => $ponds['N-01']->id,
                'to_pond_id' => $ponds['P-02']->id,
                'fish_species_id' => $species['Catla']->id,
                'quantity' => 500,
                'transferred_on' => now()->subDays(15)->toDateString(),
                'reference' => 'TRF-DEMO-01',
                'note' => 'Sample transfer.',
                'created_by' => $actor,
            ]);
        }

        // Feed purchases / usage / adjustment.
        if ($feedTypes['Grower Pellet']->purchases()->count() === 0) {
            $feed->recordPurchase([
                'feed_type_id' => $feedTypes['Grower Pellet']->id,
                'quantity_kg' => 1000,
                'unit_cost' => 39,
                'purchased_on' => now()->subDays(60)->toDateString(),
                'invoice_no' => 'DEMO-INV-001',
                'supplier_name' => 'Demo Feed Mills',
                'paid_amount' => 39000,
                'created_by' => $actor,
            ]);
        }

        if ($feedTypes['Starter Feed']->purchases()->count() === 0) {
            $feed->recordPurchase([
                'feed_type_id' => $feedTypes['Starter Feed']->id,
                'quantity_kg' => 400,
                'unit_cost' => 44,
                'purchased_on' => now()->subDays(50)->toDateString(),
                'invoice_no' => 'DEMO-INV-002',
                'supplier_name' => 'Demo Feed Mills',
                'created_by' => $actor,
            ]);
        }

        if ($feedTypes['Finisher Feed']->purchases()->count() === 0) {
            // Deliberately small so this type reads as LOW stock on the dashboard.
            $feed->recordPurchase([
                'feed_type_id' => $feedTypes['Finisher Feed']->id,
                'quantity_kg' => 50,
                'unit_cost' => 45,
                'purchased_on' => now()->subDays(30)->toDateString(),
                'supplier_name' => 'Demo Feed Mills',
                'created_by' => $actor,
            ]);
        }

        if (\App\Models\FeedUsage::query()->count() === 0) {
            foreach (
                [
                    [$ponds['P-01'], $feedTypes['Grower Pellet'], 120, 30],
                    [$ponds['P-01'], $feedTypes['Grower Pellet'], 140, 20],
                    [$ponds['P-02'], $feedTypes['Starter Feed'], 60, 25],
                    [$ponds['N-01'], $feedTypes['Starter Feed'], 40, 15],
                ] as [$pond, $type, $kg, $daysAgo]
            ) {
                $feed->recordUsage([
                    'pond_id' => $pond->id,
                    'feed_type_id' => $type->id,
                    'quantity_kg' => $kg,
                    'used_on' => now()->subDays($daysAgo)->toDateString(),
                    'created_by' => $actor,
                ]);
            }
        }

        if ($feedTypes['Grower Pellet']->adjustments()->count() === 0) {
            $feed->recordAdjustment([
                'feed_type_id' => $feedTypes['Grower Pellet']->id,
                'direction' => 'out',
                'quantity_kg' => 15,
                'reason' => 'spoilage',
                'note' => 'Sample adjustment — damp bags discarded.',
                'adjusted_on' => now()->subDays(10)->toDateString(),
                'created_by' => $actor,
            ]);
        }

        // Pond ledger entries: income + expenses per pond.
        if (\App\Models\PondLedgerEntry::query()->count() === 0) {
            $entries = [
                ['P-01', 'debit', 'fingerlings', 36000.00, 90, 'Stocking cost'],
                ['P-01', 'debit', 'feed', 15600.00, 30, 'Feed for cycle'],
                ['P-01', 'debit', 'labour', 8000.00, 45, 'Pond labour'],
                ['P-01', 'credit', 'fish_sale', 96000.00, 20, 'Partial harvest sale'],
                ['P-02', 'debit', 'fingerlings', 21000.00, 75, 'Stocking cost'],
                ['P-02', 'debit', 'electricity', 4500.00, 25, 'Pumping'],
                ['P-02', 'credit', 'fish_sale', 28000.00, 10, 'Advance sale'],
                ['N-01', 'debit', 'fingerlings', 10000.00, 45, 'Fry purchase'],
                ['N-01', 'debit', 'medicine', 2500.00, 20, 'Treatment'],
                // N-02 intentionally has NO entries — proves the empty-state honestly.
            ];

            foreach ($entries as [$pondKey, $type, $category, $amount, $daysAgo, $description]) {
                $ledger->record([
                    'pond_id' => $ponds[$pondKey]->id,
                    'entry_type' => $type,
                    'category' => $category,
                    'amount' => $amount,
                    'entry_date' => now()->subDays($daysAgo)->toDateString(),
                    'reference' => 'DEMO-LED-' . strtoupper($pondKey) . '-' . $category,
                    'source_type' => 'manual',
                    'description' => $description,
                    'created_by' => $actor,
                ]);
            }
        }

        // --- FCR & Growth: samples, inspections and a schedule -------------
        // Growth samples make FCR computable: weight gain = (latest − starting)
        // × live stock. Two samples on a pond give a growth trend; a pond with a
        // single sample honestly reports no gain yet.
        if (\App\Models\GrowthRecord::query()->count() === 0) {
            $growthService = app(\App\Services\Fcr\GrowthService::class);

            $samples = [
                // P-01: 25 g at stocking → growth to ~410 g over three samples.
                ['P-01', 60, 70.00, 40],
                ['P-01', 30, 220.00, 35],
                ['P-01', 7, 410.00, 30],
                // P-02: 20 g at stocking → growth to ~280 g.
                ['P-02', 45, 95.00, 35],
                ['P-02', 10, 280.00, 30],
                // N-01: 8 g fingerlings → 55 g (nursery, much slower).
                ['N-01', 20, 26.00, 40],
                ['N-01', 5, 55.00, 35],
            ];

            foreach ($samples as [$pondKey, $daysAgo, $weight, $sampleSize]) {
                $growthService->record([
                    'pond_id' => $ponds[$pondKey]->id,
                    'sampled_on' => now()->subDays($daysAgo)->toDateString(),
                    'avg_weight_g' => $weight,
                    'sample_size' => $sampleSize,
                    'note' => 'Sample created by DemoDataSeeder.',
                    'created_by' => $actor,
                ]);
            }
        }

        // Inspections: a healthy history plus one flagged concern so the
        // "needs attention" states are visible.
        if (\App\Models\Inspection::query()->count() === 0) {
            $inspectionService = app(\App\Services\Fcr\InspectionService::class);

            $inspections = [
                // [pond, daysAgo, ph, temp, do, ammonia, turbidity, status, by, action]
                ['P-01', 50, '7.40', '28.50', '6.80', '0.050', '18.00', 'healthy', 'Demo Field Officer', null],
                ['P-01', 25, '7.20', '29.10', '6.20', '0.080', '22.00', 'healthy', 'Demo Field Officer', null],
                ['P-01', 6, '6.60', '31.50', '4.10', '0.320', '41.00', 'warning', 'Demo Field Officer', 'Reduced feed, increased aeration.'],
                ['P-02', 30, '7.50', '28.00', '7.10', '0.040', '15.00', 'healthy', 'Demo Field Officer', null],
                ['P-02', 8, '7.30', '28.60', '6.60', '0.060', '19.00', 'healthy', 'Demo Field Officer', null],
                ['N-01', 15, '7.80', '27.40', '7.40', '0.030', '12.00', 'healthy', 'Demo Field Officer', null],
                // One inspection with NO readings, proving "not measured" stays blank.
                ['P-03', 12, null, null, null, null, null, 'monitor', 'Demo Field Officer', null],
            ];

            foreach ($inspections as [$pondKey, $daysAgo, $ph, $temp, $do, $ammonia, $turbidity, $status, $by, $action]) {
                $inspectionService->record([
                    'pond_id' => $ponds[$pondKey]->id,
                    'inspected_on' => now()->subDays($daysAgo)->toDateString(),
                    'inspected_by' => $by,
                    'water_ph' => $ph,
                    'water_temp_c' => $temp,
                    'dissolved_oxygen' => $do,
                    'ammonia' => $ammonia,
                    'turbidity' => $turbidity,
                    'health_status' => $status,
                    'action_taken' => $action,
                    'created_by' => $actor,
                ]);
            }
        }

        // Schedules: one weekly, one monthly, one deliberately overdue, leaving
        // two ponds without a schedule so the coverage warning is visible.
        if (\App\Models\InspectionSchedule::query()->count() === 0) {
            $scheduleService = app(\App\Services\Fcr\InspectionScheduleService::class);

            $scheduleService->setForPond($ponds['P-01'], [
                'frequency' => 'weekly',
                'last_completed_on' => now()->subDays(6)->toDateString(),
                'is_active' => true,
            ]);

            // Deliberately overdue: last inspection 40 days ago on a weekly plan.
            $scheduleService->setForPond($ponds['P-02'], [
                'frequency' => 'weekly',
                'last_completed_on' => now()->subDays(40)->toDateString(),
                'is_active' => true,
            ]);

            $scheduleService->setForPond($ponds['N-01'], [
                'frequency' => 'monthly',
                'last_completed_on' => now()->subDays(10)->toDateString(),
                'is_active' => true,
            ]);
        }

        $this->command?->info('Demo data ready. Visit: /fish-farm/ponds, /fish-farm/fish, /fish-farm/feed, /fish-farm/pond-ledger, /fish-farm/fcr, /dashboard');
        $this->command?->warn('Remove it later with: php artisan db:seed --class=DemoDataSeeder --command=remove');
    }

    /**
     * Remove every record this seeder created.
     *
     * Deletes children before parents so foreign keys are respected, and only
     * touches rows that belong to the demo ponds / demo catalogue entries.
     */
    public function remove(): void
    {
        $this->command?->info('Removing demo data…');

        DB::transaction(function (): void {
            $pondIds = Pond::query()
                ->where('name', 'like', self::PREFIX . '%')
                ->pluck('id');

            $feedTypeIds = FeedType::query()
                ->where('name', 'like', self::PREFIX . '%')
                ->pluck('id');

            // Movements first (they reference ponds / feed types).
            \App\Models\PondTransfer::query()
                ->whereIn('from_pond_id', $pondIds)
                ->orWhereIn('to_pond_id', $pondIds)
                ->delete();

            \App\Models\PondLedgerEntry::query()->whereIn('pond_id', $pondIds)->delete();
            \App\Models\FishStocking::query()->whereIn('pond_id', $pondIds)->delete();
            \App\Models\FishMortality::query()->whereIn('pond_id', $pondIds)->delete();
            \App\Models\Harvest::query()->whereIn('pond_id', $pondIds)->delete();
            \App\Models\FeedUsage::query()->whereIn('feed_type_id', $feedTypeIds)->delete();
            \App\Models\FeedPurchase::query()->whereIn('feed_type_id', $feedTypeIds)->delete();
            \App\Models\FeedStockAdjustment::query()->whereIn('feed_type_id', $feedTypeIds)->delete();

            // Phase 6 — FCR & Growth (inspection_schedules cascade with the pond).
            \App\Models\GrowthRecord::query()->whereIn('pond_id', $pondIds)->delete();
            \App\Models\Inspection::query()->whereIn('pond_id', $pondIds)->delete();
            \App\Models\InspectionSchedule::query()->whereIn('pond_id', $pondIds)->delete();

            FeedType::query()->whereIn('id', $feedTypeIds)->delete();
            Pond::query()->whereIn('id', $pondIds)->delete();

            FishSpecies::query()->where('name', 'like', self::PREFIX . '%')->delete();
            PondType::query()->where('name', 'like', self::PREFIX . '%')->delete();
        });

        $this->command?->info('Demo data removed.');
    }
}
