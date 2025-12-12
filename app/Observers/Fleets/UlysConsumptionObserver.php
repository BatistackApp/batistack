<?php

namespace App\Observers\Fleets;

use App\Models\Fleets\UlysConsumption;
use App\Services\Comptabilite\UlysComptaService;

class UlysConsumptionObserver
{
    /**
     * Handle the UlysConsumption "created" event.
     * @throws \Throwable
     */
    public function created(UlysConsumption $ulysConsumption): void
    {
        /** @var UlysComptaService $service */
        $service = app(UlysComptaService::class);
        $service->postUlysConsumptionEntry($ulysConsumption);
    }

    /**
     * Handle the UlysConsumption "updated" event.
     */
    public function updated(UlysConsumption $ulysConsumption): void
    {
        //
    }

    /**
     * Handle the UlysConsumption "deleted" event.
     */
    public function deleted(UlysConsumption $ulysConsumption): void
    {
        //
    }

    /**
     * Handle the UlysConsumption "restored" event.
     */
    public function restored(UlysConsumption $ulysConsumption): void
    {
        //
    }

    /**
     * Handle the UlysConsumption "force deleted" event.
     */
    public function forceDeleted(UlysConsumption $ulysConsumption): void
    {
        //
    }
}
