<?php

namespace App\Observers\Locations;

use App\Models\Locations\RentalContract;
use Illuminate\Support\Carbon;

class RentalContractObserver
{
    /**
     * Handle the RentalContract "saving" event.
     * This event is fired before a model is created or updated.
     */
    public function saving(RentalContract $rentalContract): void
    {
        // Calculer le montant total si les dates et le tarif sont définis
        if ($rentalContract->start_date && $rentalContract->end_date && $rentalContract->daily_rate) {
            $startDate = Carbon::parse($rentalContract->start_date);
            $endDate = Carbon::parse($rentalContract->end_date);
            $days = $startDate->diffInDays($endDate) + 1; // +1 pour inclure le jour de début

            $rentalContract->total_amount = $days * $rentalContract->daily_rate;
        }
    }

    /**
     * Handle the RentalContract "created" event.
     */
    public function created(RentalContract $rentalContract): void
    {
        // TODO: Envoyer une notification de création de contrat
    }

    /**
     * Handle the RentalContract "updated" event.
     */
    public function updated(RentalContract $rentalContract): void
    {
        // TODO: Envoyer une notification de mise à jour de contrat
    }

    /**
     * Handle the RentalContract "deleted" event.
     */
    public function deleted(RentalContract $rentalContract): void
    {
        // TODO: Envoyer une notification d'annulation de contrat
    }
}
