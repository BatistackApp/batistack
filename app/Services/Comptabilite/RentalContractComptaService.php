<?php

namespace App\Services\Comptabilite;

use App\Enums\Facturation\SalesDocumentLineType;
use App\Enums\Facturation\SalesDocumentStatus;
use App\Enums\Facturation\SalesDocumentType;
use App\Models\Facturation\SalesDocument;
use App\Models\Locations\RentalContract;
use Illuminate\Support\Facades\Log;
use Exception;

class RentalContractComptaService
{
    /**
     * Crée une facture (SalesDocument) à partir d'un contrat de location.
     *
     * @param RentalContract $contract
     * @return void
     * @throws Exception
     */
    public function generateInvoiceFromContract(RentalContract $contract): void
    {
        // Vérifier si une facture a déjà été générée pour ce contrat
        if (SalesDocument::where('sourceable_type', RentalContract::class)
            ->where('sourceable_id', $contract->id)
            ->exists()) {
            Log::warning("Une facture a déjà été générée pour le contrat de location {$contract->reference}.");
            return;
        }

        // Hypothèse d'un taux de TVA standard de 20%
        $vatRate = 0.20;
        $totalTTC = $contract->total_ttc;
        $totalHT = $totalTTC / (1 + $vatRate);
        $totalVAT = $totalTTC - $totalHT;

        // Création de la facture
        $invoice = SalesDocument::create([
            'company_id' => $contract->company_id,
            'tiers_id' => $contract->tiers_id,
            'chantiers_id' => $contract->chantiers_id,
            'type' => SalesDocumentType::Invoice,
            'status' => SalesDocumentStatus::Draft, // La facture est créée en brouillon
            'date' => now(),
            'due_date' => now()->addDays(30),
            'total_ht' => $totalHT,
            'total_vat' => $totalVAT,
            'total_ttc' => $totalTTC,
            'notes' => "Facture générée à partir du contrat de location {$contract->reference}.",
            'sourceable_type' => RentalContract::class,
            'sourceable_id' => $contract->id,
        ]);

        // Création de la ligne de facture
        $invoice->lines()->create([
            'product_name' => "Location de matériel: {$contract->fleet->name}",
            'description' => "Période du {$contract->start_date->format('d/m/Y')} au {$contract->end_date->format('d/m/Y')}",
            'quantity' => 1,
            'unit_price_ht' => $totalHT,
            'vat_rate' => $vatRate * 100,
            'type' => SalesDocumentLineType::Service,
        ]);

        Log::info("Facture {$invoice->reference} créée avec succès à partir du contrat de location {$contract->reference}.");
    }
}
