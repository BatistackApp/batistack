<?php

namespace App\Jobs\Banque;

use App\Models\Banque\BankTransaction;
use App\Models\Banque\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class AutoReconcileTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public BankTransaction $transaction)
    {
    }

    public function handle(): void
    {
        if ($this->transaction->reconciled_at) {
            return;
        }

        $matchingPayment = Payment::whereNull('bank_transaction_id')
            ->where('amount', $this->transaction->amount)
            ->where(function($query) {
                $query->where('bank_account_id', $this->transaction->bank_account_id)
                    ->orWhereNull('bank_account_id');
            })
            ->whereBetween('date', [
                $this->transaction->date->subDays(5),
                $this->transaction->date->addDays(5)
            ])
            ->first();

        if ($matchingPayment) {
            // MATCH TROUVÉ !

            // 1. On lie le paiement à la transaction
            $matchingPayment->update([
                'bank_transaction_id' => $this->transaction->id,
                'bank_account_id' => $this->transaction->bank_account_id, // On force le bon compte
                'status' => 'cleared', // Encaissé
            ]);

            // 2. On marque la transaction comme rapprochée
            $this->transaction->update([
                'reconciled_at' => now(),
            ]);

            // Note : L'Observer de Payment (vu ci-dessous) s'occupera de mettre à jour la Facture.
        } else {
            Log::info("Rapprochement auto: Aucun match trouvé pour Transaction #{$this->transaction->id}.");
        }
    }
}
