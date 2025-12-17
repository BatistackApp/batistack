<?php

namespace App\Observers\Locations;

use App\Enums\Locations\RentalContractStatus;
use App\Models\Locations\RentalContract;
use App\Services\Comptabilite\RentalContractComptaService;
use Illuminate\Support\Facades\Log;

class RentalContractObserver
{
    /**
     * Handle the RentalContract "created" event.
     */
    public function created(RentalContract $contract): void
    {
        if ($contract->chantiers_id) {
            $contract->chantiers->increment('total_rental_cost', $contract->total_ttc);
        }
    }

    /**
     * Handle the RentalContract "updated" event.
     */
    public function updated(RentalContract $contract): void
    {
        // Si le coût a changé
        if ($contract->isDirty('total_ttc')) {
            $oldCost = $contract->getOriginal('total_ttc');
            $newCost = $contract->total_ttc;
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
                    $oldChantier->decrement('total_rental_cost', $contract->getOriginal('total_ttc'));
                }
            }
            // Ajouter le nouveau coût au nouveau chantier
            if ($contract->chantiers_id) {
                $contract->chantiers->increment('total_rental_cost', $contract->total_ttc);
            }
        }

        // Si le statut vient de passer à "Active"
        if ($contract->isDirty('status') && $contract->status === RentalContractStatus::Active) {
            try {
                $comptaService = new RentalContractComptaService();
                $comptaService->postRentalContractEntry($contract);
                Log::info("Contrat de location {$contract->reference} comptabilisé avec succès.");
            } catch (\Exception $e) {
                Log::error("Erreur lors de la comptabilisation du contrat de location {$contract->reference}: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle the RentalContract "deleted" event.
     */
    public function deleted(RentalContract $contract): void
    {
        if ($contract->chantiers_id) {
            $contract->chantiers->decrement('total_rental_cost', $contract->total_ttc);
        }
    }
}
