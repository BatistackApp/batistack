<?php

namespace App\Services\Comptabilite;

use App\Enums\Comptabilite\JournalType;
use App\Models\Comptabilite\ComptaAccount;
use App\Models\Comptabilite\ComptaEntry;
use App\Models\Comptabilite\ComptaJournal;
use App\Models\Core\Company;
use App\Models\Facturation\SalesDocument;
use Exception;
use Illuminate\Support\Facades\DB;

class SalesDocumentComptaService
{
    /**
     * Crée les écritures comptables pour un document de vente (facture, avoir, etc.).
     *
     * @param SalesDocument $salesDocument
     * @return void
     * @throws Exception
     */
    public function postSalesDocumentEntry(SalesDocument $salesDocument): void
    {
        // Vérifier si les écritures ont déjà été passées pour ce document
        if (ComptaEntry::where('sourceable_type', SalesDocument::class)
            ->where('sourceable_id', $salesDocument->id)
            ->exists()) {
            throw new Exception("Les écritures comptables pour le document de vente {$salesDocument->reference} ont déjà été passées.");
        }

        $company = $salesDocument->company;

        // Récupérer le journal de vente
        $journal = ComptaJournal::where('company_id', $company->id)
            ->where('type', JournalType::Sale)
            ->first();

        if (!$journal) {
            throw new Exception("Journal de vente non trouvé pour la compagnie {$company->name}.");
        }

        // Récupérer les comptes comptables nécessaires (à définir dans la configuration de la compagnie ou par défaut)
        // Pour l'exemple, nous allons utiliser des comptes fictifs. En réalité, ces comptes devraient être configurables.
        $accountClient = ComptaAccount::where('company_id', $company->id)
            ->where('number', '411000') // Compte client générique
            ->first();
        if (!$accountClient) {
            throw new Exception("Compte client (411000) non trouvé pour la compagnie {$company->name}.");
        }

        $accountSales = ComptaAccount::where('company_id', $company->id)
            ->where('number', '707000') // Compte de vente de marchandises/prestations
            ->first();
        if (!$accountSales) {
            throw new Exception("Compte de vente (707000) non trouvé pour la compagnie {$company->name}.");
        }

        $accountVatCollect = ComptaAccount::where('company_id', $company->id)
            ->where('number', '445710') // Compte TVA collectée
            ->first();
        if (!$accountVatCollect) {
            throw new Exception("Compte TVA collectée (445710) non trouvé pour la compagnie {$company->name}.");
        }

        DB::transaction(function () use ($salesDocument, $journal, $accountClient, $accountSales, $accountVatCollect) {
            $totalTTC = $salesDocument->total_ttc;
            $totalHT = $salesDocument->total_ht;
            $totalVAT = $salesDocument->total_vat;

            // Écriture 1 : Débit du compte client (Total TTC)
            ComptaEntry::create([
                'company_id' => $salesDocument->company_id,
                'journal_id' => $journal->id,
                'account_id' => $accountClient->id,
                'tier_id' => $salesDocument->tiers_id, // Le tiers est le client
                'date' => $salesDocument->document_date,
                'label' => "Facture {$salesDocument->reference} - Client {$salesDocument->tiers->name}",
                'debit' => $totalTTC,
                'credit' => 0,
                'sourceable_type' => SalesDocument::class,
                'sourceable_id' => $salesDocument->id,
            ]);

            // Écriture 2 : Crédit du compte de vente (Total HT)
            ComptaEntry::create([
                'company_id' => $salesDocument->company_id,
                'journal_id' => $journal->id,
                'account_id' => $accountSales->id,
                'tier_id' => null, // Pas de tiers direct pour le compte de vente
                'date' => $salesDocument->document_date,
                'label' => "Vente {$salesDocument->reference}",
                'debit' => 0,
                'credit' => $totalHT,
                'sourceable_type' => SalesDocument::class,
                'sourceable_id' => $salesDocument->id,
            ]);

            // Écriture 3 : Crédit du compte de TVA collectée (Total TVA)
            ComptaEntry::create([
                'company_id' => $salesDocument->company_id,
                'journal_id' => $journal->id,
                'account_id' => $accountVatCollect->id,
                'tier_id' => null, // Pas de tiers direct pour le compte de TVA
                'date' => $salesDocument->document_date,
                'label' => "TVA collectée {$salesDocument->reference}",
                'debit' => 0,
                'credit' => $totalVAT,
                'sourceable_type' => SalesDocument::class,
                'sourceable_id' => $salesDocument->id,
            ]);

            // Optionnel : Mettre à jour le statut du document de vente pour indiquer qu'il a été comptabilisé
            // $salesDocument->update(['is_posted_to_compta' => true]); // Nécessiterait un champ dans la table sales_documents
        });
    }
}
