<?php

namespace App\Observers\Facturation;

use App\Enums\Facturation\PurchaseDocumentStatus;
use App\Models\Facturation\PurchaseDocument;
use App\Services\Comptabilite\PurchaseDocumentComptaService;
use Illuminate\Support\Facades\Log;

class PurchaseDocumentObserver
{
    /**
     * Handle the PurchaseDocument "saved" event.
     * This event is fired after a model is created or updated.
     */
    public function saved(PurchaseDocument $purchaseDocument): void
    {
        // Comptabilisation des factures fournisseurs
        if ($purchaseDocument->isDirty('status')) {
            $newStatus = $purchaseDocument->status;

            // Si la facture passe à "Approuvé" ou "Payé"
            // et qu'elle n'a pas déjà été comptabilisée (le service gère la vérification)
            if (in_array($newStatus, [PurchaseDocumentStatus::Approved, PurchaseDocumentStatus::Paid])) {
                try {
                    $comptaService = new PurchaseDocumentComptaService();
                    $comptaService->postPurchaseDocumentEntry($purchaseDocument);
                    Log::info("Facture fournisseur {$purchaseDocument->reference} comptabilisée avec succès.");
                } catch (\Exception $e) {
                    Log::error("Erreur lors de la comptabilisation de la facture fournisseur {$purchaseDocument->reference}: " . $e->getMessage());
                    // Optionnel: Notifier l'administrateur ou l'utilisateur de l'échec
                }
            }
        }
    }
}
