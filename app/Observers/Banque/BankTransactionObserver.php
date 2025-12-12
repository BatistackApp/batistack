<?php

namespace App\Observers\Banque;

use App\Jobs\Banque\AutoReconcileTransactionJob;
use App\Models\Banque\BankTransaction;

class BankTransactionObserver
{
    /**
     * Déclenche le rapprochement automatique après la création d'une nouvelle transaction.
     */
    public function created(BankTransaction $transaction): void
    {
        // Ne déclenche que si l'external_id est présent (signifie une transaction importée)
        if ($transaction->external_id) {
            AutoReconcileTransactionJob::dispatch($transaction);
        }
    }

    public function deleting(BankTransaction $transaction): void
    {
        // On empêche la suppression si la transaction est rapprochée
        if ($transaction->reconciled_at) {
            // En Laravel/Filament, on peut lancer une exception ou annuler
            // abort(403, "Impossible de supprimer une transaction rapprochée.");
            abort(403, "Impossible de supprimer une transaction rapprochée.");
        }
    }
}
