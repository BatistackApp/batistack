<?php

namespace App\Observers\Locations;

use App\Models\Locations\RentalContract;

class RentalContractObserver
{
    /**
     * Handle the RentalContract "created" event.
     */
    public function created(RentalContract $contract): void
    {
        if ($contract->chantiers_id) {
            $contract->chantiers->increment('total_rental_cost', $contract->total_ttc ?? 0);
        }
    }

    /**
     * Handle the RentalContract "updated" event.
     */
    public function updated(RentalContract $contract): void
    {
        // Si le coût a changé
        if ($contract->isDirty('total_ttc')) {
            $oldCost = $contract->getOriginal('total_ttc') ?? 0;
            $newCost = $contract->total_ttc ?? 0;

            if ($contract->chantiers_id) {
                $contract->chantiers->increment('total_rental_cost', $newCost - $oldCost);
            }
        }

        // Si le chantier a changé
        if ($contract->isDirty('chantiers_id')) {
            // Retirer l'ancien coût de l'ancien chantier
            if ($contract->getOriginal('chantiers_id')) {
                $oldChantier = \App\Models\Chantiers\Chantiers::find($contract->getOriginal('chantiers_id'));
                if ($oldChantier) {
                    $oldChantier->decrement('total_rental_cost', $contract->getOriginal('total_ttc') ?? 0);
                }
            }
            // Ajouter le nouveau coût au nouveau chantier
            if ($contract->chantiers_id) {
                $contract->chantiers->increment('total_rental_cost', $contract->total_ttc ?? 0);
            }
        }
    }

    /**
     * Handle the RentalContract "deleted" event.
     */
    public function deleted(RentalContract $contract): void
    {
        if ($contract->chantiers_id) {
            $contract->chantiers->decrement('total_rental_cost', $contract->total_ttc ?? 0);
        }
    }
}
