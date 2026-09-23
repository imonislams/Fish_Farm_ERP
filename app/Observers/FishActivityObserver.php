<?php

namespace App\Observers;

use App\Models\FishMortality;
use App\Models\FishStocking;
use App\Models\Harvest;
use App\Models\Inspection;
use App\Models\GrowthRecord;
use App\Models\IncomeEntry;
use App\Models\ExpenseEntry;
use App\Models\CustomerPayment;
use App\Services\Notification\NotificationService;

/**
 * Fish + Finance event notifications, all from real records.
 *
 * Kept in ONE observer class per concern group to avoid a proliferation of tiny
 * files; each `created()` maps a real record to a real notification.
 */
class FishActivityObserver
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function stocking(FishStocking $s): void
    {
        $this->notifications->stockingRecorded((int) $s->quantity, $s->pond?->name);
    }

    public function mortality(FishMortality $m): void
    {
        $this->notifications->mortalityRecorded((int) $m->quantity, $m->pond?->name);
    }

    public function harvest(Harvest $h): void
    {
        $this->notifications->harvestRecorded((int) $h->quantity, $h->pond?->name);
    }

    public function inspection(Inspection $i): void
    {
        $this->notifications->inspectionRecorded($i->healthLabel(), $i->pond?->name);
    }

    public function growth(GrowthRecord $g): void
    {
        $this->notifications->growthRecorded((float) $g->avg_weight_g, $g->pond?->name);
    }

    public function income(IncomeEntry $e): void
    {
        $this->notifications->incomeRecorded((float) $e->amount);
    }

    public function expense(ExpenseEntry $e): void
    {
        $this->notifications->expenseRecorded((float) $e->amount);
    }

    public function customerPayment(CustomerPayment $p): void
    {
        $this->notifications->paymentReceived((float) $p->amount, $p->customer?->name);
    }
}
