<?php

namespace App\Services\Comptabilite;

use App\Models\Banque\Payment;
use App\Models\Comptabilite\ComptaAccount;
use App\Models\Comptabilite\ComptaEntry;
use App\Models\Comptabilite\ComptaJournal;
use Exception;
use Illuminate\Support\Facades\DB;

class PaymentComptaService
{
    /**
     * @throws \Throwable
     */
    public function postPaymentEntry(Payment $payment): void
    {
        if (ComptaEntry::where('sourceable_type', Payment::class)->where('sourceable_id', $payment->id)->exists()) {
            throw new Exception("Le paiement #{$payment->id} a déjà été comptabilisé.");
        }

        $company = $payment->company;
        $journal = ComptaJournal::where('company_id', $company->id)->where('type', 'bank')->firstOrFail();
        $waitingAccount = ComptaAccount::where('company_id', $company->id)->where('number', '471000')->firstOrFail();
        $tierAccount = $payment->payable->getComptaAccount(); // Suppose une méthode sur le modèle payable

        DB::transaction(function () use ($payment, $journal, $waitingAccount, $tierAccount, $company) {
            $label = "Règlement {$payment->payable->reference}";

            // Écriture 1 : On solde le compte d'attente
            ComptaEntry::create([
                'company_id' => $company->id,
                'journal_id' => $journal->id,
                'account_id' => $waitingAccount->id,
                'date' => $payment->date,
                'label' => $label,
                'debit' => $payment->is_incoming ? 0 : $payment->amount,
                'credit' => $payment->is_incoming ? $payment->amount : 0,
                'sourceable_type' => Payment::class,
                'sourceable_id' => $payment->id,
            ]);

            // Écriture 2 : On mouvement le compte du tiers
            ComptaEntry::create([
                'company_id' => $company->id,
                'journal_id' => $journal->id,
                'account_id' => $tierAccount->id,
                'tier_id' => $payment->tiers_id,
                'date' => $payment->date,
                'label' => $label,
                'debit' => $payment->is_incoming ? $payment->amount : 0,
                'credit' => $payment->is_incoming ? 0 : $payment->amount,
                'sourceable_type' => Payment::class,
                'sourceable_id' => $payment->id,
            ]);
        });
    }
}
