<?php

namespace App\Observers\Locations;

use App\Enums\Locations\RentalContractStatus;
use App\Models\Locations\RentalContract;
use App\Services\Comptabilite\RentalContractComptaService;
use Illuminate\Support\Facades\Log;

class RentalContractObserver
{
    /**
     * Handle the RentalContract "updated" event.
     */
    public function updated(RentalContract $contract): void
    {
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
}
