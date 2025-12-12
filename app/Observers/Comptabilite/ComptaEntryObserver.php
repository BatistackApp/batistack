<?php

namespace App\Observers\Comptabilite;

use App\Models\Comptabilite\ComptaEntry;
use Exception;

class ComptaEntryObserver
{
    /**
     * @throws Exception
     */
    public function saving(ComptaEntry $entry): void
    {
        $closedAt = $entry->company->accounting_closed_at;

        if ($closedAt && $entry->date <= $closedAt) {
            // En production, on utiliserait une Exception métier spécifique ou on bloquerait silencieusement selon l'UX
            throw new Exception("Impossible de modifier une écriture sur une période comptable clôturée (Date de clôture : {$closedAt->format('d/m/Y')}).");
        }

        if ($entry->exists && $entry->sourceable_type && $entry->isDirty(['debit', 'credit', 'account_id'])) {
            // Optionnel : Bloquer la modif manuelle des écritures générées par Ulys/Facturation
            throw new Exception("Cette écriture est liée à une source automatique ({$entry->sourceable_type}). Veuillez modifier la source ou passer une OD de correction.");
        }
    }

    /**
     * @throws Exception
     */
    public function deleting(ComptaEntry $entry): void
    {
        $closedAt = $entry->company->accounting_closed_at;

        if ($closedAt && $entry->date <= $closedAt) {
            throw new Exception("Impossible de supprimer une écriture sur une période comptable clôturée.");
        }
    }
}
