<?php

namespace App\Services\Comptabilite;

use App\Enums\Facturation\PurchaseDocumentStatus;
use App\Models\Facturation\PurchaseDocument;
use App\Models\Locations\RentalContract;
use Illuminate\Support\Facades\Log;
use Exception;

class RentalContractComptaService
{
    /**
     * Crée une facture fournisseur (PurchaseDocument) à partir d'un contrat de location.
     *
     * @param RentalContract $contract
     * @return void
     * @throws Exception
     */
    public function generateInvoiceFromContract(RentalContract $contract): void
    {
        // Vérifier si une facture a déjà été générée pour ce contrat
        if (PurchaseDocument::where('sourceable_type', RentalContract::class)
            ->where('sourceable_id', $contract->id)
            ->exists()) {
            Log::warning("Une facture fournisseur a déjà été générée pour le contrat de location #{$contract->id}.");
            return;
        }

        // Création de la facture fournisseur
        $invoice = PurchaseDocument::create([
            'company_id' => $contract->company_id,
            'tiers_id' => $contract->tiers_id,
            'chantiers_id' => $contract->chantiers_id,
            'status' => PurchaseDocumentStatus::Draft, // La facture est créée en brouillon pour validation
            'date' => now(),
            'due_date' => now()->addDays(30), // Par défaut, à ajuster selon les conditions fournisseur
            'total_ht' => $contract->total_ht,
            'total_vat' => $contract->total_vat,
            'total_ttc' => $contract->total_ttc,
            'notes' => "Facture générée automatiquement à partir du contrat de location #{$contract->id} (Période : {$contract->start_date->format('d/m/Y')} - {$contract->end_date->format('d/m/Y')})",
            'sourceable_type' => RentalContract::class,
            'sourceable_id' => $contract->id,
        ]);

        // Création des lignes de facture à partir des lignes du contrat
        foreach ($contract->lines as $line) {
            $invoice->lines()->create([
                'description' => $line->product ? $line->product->name : "Location diverse",
                'quantity' => $line->quantity,
                'unit_price' => $line->unit_price,
                'vat_rate' => $line->vat_rate,
                'total_ht' => $line->total_ht,
                'total_vat' => $line->total_vat,
                'total_ttc' => $line->total_ttc,
                // Si PurchaseDocumentLine a un champ product_id ou account_id, le mapper ici
                // 'product_id' => $line->product_id,
            ]);
        }

        // Marquer le contrat comme comptabilisé
        $contract->updateQuietly(['is_posted_to_compta' => true]);

        Log::info("Facture fournisseur #{$invoice->id} créée avec succès à partir du contrat de location #{$contract->id}.");
    }
}
