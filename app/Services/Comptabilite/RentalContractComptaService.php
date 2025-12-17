<?php

namespace App\Services\Comptabilite;

use App\Enums\Comptabilite\JournalType;
use App\Models\Comptabilite\ComptaAccount;
use App\Models\Comptabilite\ComptaEntry;
use App\Models\Comptabilite\ComptaJournal;
use App\Models\Locations\RentalContract;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RentalContractComptaService
{
    /**
     * Crée les écritures comptables pour un contrat de location.
     *
     * @param RentalContract $contract
     * @return void
     * @throws Exception
     */
    public function postRentalContractEntry(RentalContract $contract): void
    {
        if ($contract->is_posted_to_compta) {
            Log::warning("Le contrat de location {$contract->reference} a déjà été comptabilisé.");
            return;
        }

        $company = $contract->company;

        // Récupérer le journal d'achat
        $journal = ComptaJournal::where('company_id', $company->id)
            ->where('type', JournalType::Purchase)
            ->first();

        if (!$journal) {
            throw new Exception("Journal d'achat non trouvé pour la compagnie {$company->name}.");
        }

        // Récupérer les comptes comptables nécessaires (à adapter)
        $accountSupplier = ComptaAccount::where('company_id', $company->id)->where('number', '401000')->firstOrFail();
        $accountRentalCharge = ComptaAccount::where('company_id', $company->id)->where('number', '613500')->firstOrFail(); // Locations mobilières
        $accountVatDeduct = ComptaAccount::where('company_id', $company->id)->where('number', '445660')->firstOrFail();

        DB::transaction(function () use ($contract, $journal, $accountSupplier, $accountRentalCharge, $accountVatDeduct) {
            $totalTTC = $contract->total_ttc;
            $totalHT = $contract->total_ht;
            $totalVAT = $contract->total_vat;

            // Écriture 1 : Débit du compte de charge (Total HT)
            ComptaEntry::create([
                'company_id' => $contract->company_id,
                'journal_id' => $journal->id,
                'account_id' => $accountRentalCharge->id,
                'date' => $contract->start_date,
                'label' => "Location {$contract->reference} - Fournisseur {$contract->tiers->name}",
                'debit' => $totalHT,
                'credit' => 0,
                'sourceable_type' => RentalContract::class,
                'sourceable_id' => $contract->id,
            ]);

            // Écriture 2 : Débit du compte de TVA déductible (Total TVA)
            if ($totalVAT > 0) {
                ComptaEntry::create([
                    'company_id' => $contract->company_id,
                    'journal_id' => $journal->id,
                    'account_id' => $accountVatDeduct->id,
                    'date' => $contract->start_date,
                    'label' => "TVA sur location {$contract->reference}",
                    'debit' => $totalVAT,
                    'credit' => 0,
                    'sourceable_type' => RentalContract::class,
                    'sourceable_id' => $contract->id,
                ]);
            }

            // Écriture 3 : Crédit du compte fournisseur (Total TTC)
            ComptaEntry::create([
                'company_id' => $contract->company_id,
                'journal_id' => $journal->id,
                'account_id' => $accountSupplier->id,
                'tier_id' => $contract->tiers_id, // Le tiers est le fournisseur
                'date' => $contract->start_date,
                'label' => "Facture location {$contract->reference} - {$contract->tiers->name}",
                'debit' => 0,
                'credit' => $totalTTC,
                'sourceable_type' => RentalContract::class,
                'sourceable_id' => $contract->id,
            ]);

            // Marquer le contrat comme comptabilisé
            $contract->update(['is_posted_to_compta' => true]);
        });
    }
}
