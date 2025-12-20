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

        // Si le statut vient de passer à "Terminé" ou "Actif" (selon quand on veut facturer)
        // Ici on suppose qu'on comptabilise à la fin ou périodiquement.
        // Pour l'instant, gardons la logique "Completed" pour déclencher la facture finale/globale si pas de périodicité.
        if ($contract->isDirty('status') && $contract->status === RentalContractStatus::Completed) {
            // TODO: Vérifier si le service existe avant de l'instancier ou utiliser l'injection de dépendance si possible (difficile dans un observer)
            if (class_exists(RentalContractComptaService::class)) {
                try {
                    $comptaService = new RentalContractComptaService();
                    $comptaService->generateInvoiceFromContract($contract);
                    Log::info("Facture pour le contrat de location {$contract->id} générée avec succès.");
                } catch (\Exception $e) {
                    Log::error("Erreur lors de la génération de la facture pour le contrat de location {$contract->id}: " . $e->getMessage());
                }
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
