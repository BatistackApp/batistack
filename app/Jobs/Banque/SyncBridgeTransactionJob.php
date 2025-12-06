<?php

namespace App\Jobs\Banque;

use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Log;

class SyncBridgeTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public BankAccount $bankAccount)
    {
    }

    public function handle(): void
    {
        if (!$this->bankAccount->isConnectedToBridge()) {
            return;
        }

        // 1. Appel API Bridge (Pseudo-code)
        // $transactions = Bridge::getTransactions($this->bankAccount->bridge_account_id);
        // Pour l'exemple, imaginons un tableau de résultats :
        $transactions = [];

        $newCount = 0;

        foreach ($transactions as $transData) {
            // 2. Création ou Mise à jour (Idempotence via external_id)
            $transaction = BankTransaction::updateOrCreate(
                [
                    'bank_account_id' => $this->bankAccount->id,
                    'external_id' => $transData['id'], // ID unique Bridge
                ],
                [
                    'date' => $transData['date'],
                    'label' => $transData['description'],
                    'amount' => $transData['amount'],
                    'currency' => $transData['currency'],
                    'raw_data' => $transData,
                ]
            );

            if ($transaction->wasRecentlyCreated) {
                $newCount++;
                // 3. Déclencher le rapprochement auto pour cette ligne
                AutoReconcileTransactionJob::dispatch($transaction);
            }
        }

        // Mise à jour du solde du compte
        // $this->bankAccount->updateBalance($balanceFromBridge);

        if ($newCount > 0) {
            Log::info("Compte {$this->bankAccount->name} : {$newCount} nouvelles transactions.");
        }
    }
}
