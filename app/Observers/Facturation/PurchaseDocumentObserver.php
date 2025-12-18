<?php

namespace App\Observers\Facturation;

use App\Enums\Facturation\PurchaseDocumentStatus;
use App\Models\Facturation\PurchaseDocument;
use App\Services\Comptabilite\PurchaseDocumentComptaService;
use Illuminate\Support\Facades\Log;

class PurchaseDocumentObserver
{
    public function created(PurchaseDocument $document): void
    {
        if ($document->chantiers_id) {
            $document->chantiers->increment('total_purchase_cost', $document->total_ht);
        }
    }

    public function updated(PurchaseDocument $document): void
    {
        // Si le coût a changé
        if ($document->isDirty('total_ht')) {
            $oldCost = $document->getOriginal('total_ht');
            $newCost = $document->total_ht;
            if ($document->chantiers_id) {
                $document->chantiers->increment('total_purchase_cost', $newCost - $oldCost);
            }
        }

        // Si le chantier a changé
        if ($document->isDirty('chantiers_id')) {
            // Retirer l'ancien coût de l'ancien chantier
            if ($document->getOriginal('chantiers_id')) {
                $oldChantier = \App\Models\Chantiers\Chantiers::find($document->getOriginal('chantiers_id'));
                if ($oldChantier) {
                    $oldChantier->decrement('total_purchase_cost', $document->getOriginal('total_ht'));
                }
            }
            // Ajouter le nouveau coût au nouveau chantier
            if ($document->chantiers_id) {
                $document->chantiers->increment('total_purchase_cost', $document->total_ht);
            }
        }
    }

    public function deleted(PurchaseDocument $document): void
    {
        if ($document->chantiers_id) {
            $document->chantiers->decrement('total_purchase_cost', $document->total_ht);
        }
    }

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
