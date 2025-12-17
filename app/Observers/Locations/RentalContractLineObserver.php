<?php

namespace App\Observers\Locations;

use App\Models\Locations\RentalContractLine;

class RentalContractLineObserver
{
    /**
     * Handle the RentalContractLine "created" event.
     */
    public function created(RentalContractLine $line): void
    {
        $line->rentalContract->recalculate();
    }

    /**
     * Handle the RentalContractLine "updated" event.
     */
    public function updated(RentalContractLine $line): void
    {
        $line->rentalContract->recalculate();
    }

    /**
     * Handle the RentalContractLine "deleted" event.
     */
    public function deleted(RentalContractLine $line): void
    {
        $line->rentalContract->recalculate();
    }
}
