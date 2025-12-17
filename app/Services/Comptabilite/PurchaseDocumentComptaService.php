<?php

namespace App\Services\Comptabilite;

use App\Enums\Comptabilite\JournalType;
use App\Models\Comptabilite\ComptaAccount;
use App\Models\Comptabilite\ComptaEntry;
use App\Models\Comptabilite\ComptaJournal;
use App\Models\Facturation\PurchaseDocument;
use Exception;
use Illuminate\Support\Facades\DB;

class PurchaseDocumentComptaService
{
    /**
     * Crée les écritures comptables pour un document d'achat (facture fournisseur).
     *
     * @param PurchaseDocument $purchaseDocument
     * @return void
     * @throws Exception
     */
    public function postPurchaseDocumentEntry(PurchaseDocument $purchaseDocument): void
    {
        // Vérifier si les écritures ont déjà été passées pour ce document
        if ($purchaseDocument->is_posted_to_compta) {
            throw new Exception("Les écritures comptables pour le document d'achat {$purchaseDocument->reference} ont déjà été passées.");
        }

        $company = $purchaseDocument->company;

        // Récupérer le journal d'achat
        $journal = ComptaJournal::where('company_id', $company->id)
            ->where('type', JournalType::Purchase)
            ->first();

        if (!$journal) {
            throw new Exception("Journal d'achat non trouvé pour la compagnie {$company->name}.");
        }

        // Récupérer les comptes comptables nécessaires (à définir dans la configuration de la compagnie ou par défaut)
        // Pour l'exemple, nous allons utiliser des comptes fictifs. En réalité, ces comptes devraient être configurables.
        $accountSupplier = ComptaAccount::where('company_id', $company->id)
            ->where('number', '401000') // Compte fournisseur générique
            ->first();
        if (!$accountSupplier) {
            throw new Exception("Compte fournisseur (401000) non trouvé pour la compagnie {$company->name}.");
        }

        $accountPurchases = ComptaAccount::where('company_id', $company->id)
            ->where('number', '607000') // Compte d'achats de marchandises/matières
            ->first();
        if (!$accountPurchases) {
            throw new Exception("Compte d'achats (607000) non trouvé pour la compagnie {$company->name}.");
        }

        $accountVatDeduct = ComptaAccount::where('company_id', $company->id)
            ->where('number', '445660') // Compte TVA déductible
            ->first();
        if (!$accountVatDeduct) {
            throw new Exception("Compte TVA déductible (445660) non trouvé pour la compagnie {$company->name}.");
        }

        DB::transaction(function () use ($purchaseDocument, $journal, $accountSupplier, $accountPurchases, $accountVatDeduct) {
            $totalTTC = $purchaseDocument->total_ttc;
            $totalHT = $purchaseDocument->total_ht;
            $totalVAT = $purchaseDocument->total_vat;

            // Écriture 1 : Débit du compte d'achats (Total HT)
            ComptaEntry::create([
                'company_id' => $purchaseDocument->company_id,
                'journal_id' => $journal->id,
                'account_id' => $accountPurchases->id,
                'tier_id' => null, // Pas de tiers direct pour le compte d'achats
                'date' => $purchaseDocument->document_date,
                'label' => "Facture fournisseur {$purchaseDocument->reference} - {$purchaseDocument->tiers->name}",
                'debit' => $totalHT,
                'credit' => 0,
                'sourceable_type' => PurchaseDocument::class,
                'sourceable_id' => $purchaseDocument->id,
            ]);

            // Écriture 2 : Débit du compte de TVA déductible (Total TVA)
            ComptaEntry::create([
                'company_id' => $purchaseDocument->company_id,
                'journal_id' => $journal->id,
                'account_id' => $accountVatDeduct->id,
                'tier_id' => null, // Pas de tiers direct pour le compte de TVA
                'date' => $purchaseDocument->document_date,
                'label' => "TVA déductible {$purchaseDocument->reference}",
                'debit' => $totalVAT,
                'credit' => 0,
                'sourceable_type' => PurchaseDocument::class,
                'sourceable_id' => $purchaseDocument->id,
            ]);

            // Écriture 3 : Crédit du compte fournisseur (Total TTC)
            ComptaEntry::create([
                'company_id' => $purchaseDocument->company_id,
                'journal_id' => $journal->id,
                'account_id' => $accountSupplier->id,
                'tier_id' => $purchaseDocument->tiers_id, // Le tiers est le fournisseur
                'date' => $purchaseDocument->document_date,
                'label' => "Facture fournisseur {$purchaseDocument->reference} - {$purchaseDocument->tiers->name}",
                'debit' => 0,
                'credit' => $totalTTC,
                'sourceable_type' => PurchaseDocument::class,
                'sourceable_id' => $purchaseDocument->id,
            ]);

            // Mettre à jour le statut du document d'achat pour indiquer qu'il a été comptabilisé
            $purchaseDocument->update(['is_posted_to_compta' => true]);
        });
    }
}
