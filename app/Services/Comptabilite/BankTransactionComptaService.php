<?php

namespace App\Services\Comptabilite;

use App\Enums\Comptabilite\JournalType;
use App\Models\Banque\BankTransaction;
use App\Models\Comptabilite\ComptaAccount;
use App\Models\Comptabilite\ComptaEntry;
use App\Models\Comptabilite\ComptaJournal;
use Exception;
use Illuminate\Support\Facades\DB;

class BankTransactionComptaService
{
    /**
     * Crée les écritures comptables pour une transaction bancaire.
     *
     * @param BankTransaction $bankTransaction
     * @return void
     * @throws Exception|\Throwable
     */
    public function postBankTransactionEntry(BankTransaction $bankTransaction): void
    {
        // Vérifier si les écritures ont déjà été passées pour cette transaction
        if (ComptaEntry::where('sourceable_type', BankTransaction::class)
            ->where('sourceable_id', $bankTransaction->id)
            ->exists()) {
            throw new Exception("Les écritures comptables pour la transaction bancaire {$bankTransaction->label} ont déjà été passées.");
        }

        $company = $bankTransaction->account->company;

        // Récupérer le journal de banque
        $journal = ComptaJournal::where('company_id', $company->id)
            ->where('type', JournalType::Bank)
            ->first();

        if (!$journal) {
            throw new Exception("Journal de banque non trouvé pour la compagnie {$company->name}.");
        }

        // Récupérer le compte bancaire associé à la transaction
        $bankAccountCompta = ComptaAccount::where('company_id', $company->id)
            ->where('number', $bankTransaction->account->account_number) // Supposons que le numéro de compte bancaire est aussi le numéro de compte comptable
            ->first();

        if (!$bankAccountCompta) {
            // Si le compte comptable n'est pas trouvé par le numéro de compte bancaire,
            // on peut chercher un compte bancaire générique (ex: 512000) ou créer un mécanisme de mapping.
            // Pour l'instant, on lève une exception.
            throw new Exception("Compte comptable pour le compte bancaire {$bankTransaction->account->name} ({$bankTransaction->account->account_number}) non trouvé.");
        }

        DB::transaction(function () use ($bankTransaction, $journal, $bankAccountCompta, $company) {
            $amount = abs($bankTransaction->amount);
            $isDebit = $bankTransaction->amount < 0; // Si le montant est négatif, c'est un débit pour la banque (sortie d'argent)

            // Écriture 1 : Mouvement sur le compte bancaire
            ComptaEntry::create([
                'company_id' => $company->id,
                'journal_id' => $journal->id,
                'account_id' => $bankAccountCompta->id,
                'tier_id' => null, // Le tiers sera sur la contrepartie si nécessaire
                'date' => $bankTransaction->date,
                'label' => "Opération bancaire : {$bankTransaction->label}",
                'debit' => $isDebit ? $amount : 0,
                'credit' => $isDebit ? 0 : $amount,
                'sourceable_type' => BankTransaction::class,
                'sourceable_id' => $bankTransaction->id,
            ]);

            // Écriture 2 : Contrepartie (à déterminer par le rapprochement ou manuellement)
            // Pour l'instant, nous allons utiliser un compte d'attente ou un compte de liaison.
            // Dans un système réel, cette contrepartie serait déterminée par le rapprochement bancaire.
            $waitingAccount = ComptaAccount::where('company_id', $company->id)
                ->where('number', '471000') // Compte d'attente ou de liaison
                ->first();

            if (!$waitingAccount) {
                throw new Exception("Compte d'attente (471000) non trouvé pour la compagnie {$company->name}.");
            }

            ComptaEntry::create([
                'company_id' => $company->id,
                'journal_id' => $journal->id,
                'account_id' => $waitingAccount->id,
                'tier_id' => $bankTransaction->tiers_id ?? null, // Si un tiers est déjà associé à la transaction
                'date' => $bankTransaction->date,
                'label' => "Contrepartie opération bancaire : {$bankTransaction->label}",
                'debit' => $isDebit ? 0 : $amount,
                'credit' => $isDebit ? $amount : 0,
                'sourceable_type' => BankTransaction::class,
                'sourceable_id' => $bankTransaction->id,
            ]);

            // Optionnel : Mettre à jour le statut de la transaction bancaire pour indiquer qu'elle a été comptabilisée
            // $bankTransaction->update(['is_posted_to_compta' => true]); // Nécessiterait un champ dans la table bank_transactions
        });
    }
}
