<?php

namespace App\Observers\Banque;

use App\Models\Banque\BankTransaction;

class BankTransactionObserver
{
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
