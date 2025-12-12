<?php

namespace App\Services\Comptabilite;

use App\Models\Comptabilite\ComptaAccount;
use App\Models\Comptabilite\ComptaEntry;
use App\Models\Comptabilite\ComptaJournal;
use App\Models\Core\Company;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ComptaReportingService
{
    /**
     * Récupère toutes les écritures pour un journal donné sur une période.
     *
     * @param Company $company
     * @param ComptaJournal $journal
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection<ComptaEntry>
     */
    public function getJournalEntries(Company $company, ComptaJournal $journal, Carbon $startDate, Carbon $endDate): Collection
    {
        return ComptaEntry::where('company_id', $company->id)
            ->where('journal_id', $journal->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['account', 'tier']) // Charger les relations nécessaires
            ->orderBy('date')
            ->orderBy('id') // Pour un ordre stable
            ->get();
    }

    /**
     * Récupère toutes les écritures pour un compte donné sur une période (Grand Livre).
     *
     * @param Company $company
     * @param ComptaAccount $account
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection<ComptaEntry>
     */
    public function getGeneralLedgerEntries(Company $company, ComptaAccount $account, Carbon $startDate, Carbon $endDate): Collection
    {
        return ComptaEntry::where('company_id', $company->id)
            ->where('account_id', $account->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->with(['journal', 'tier']) // Charger les relations nécessaires
            ->orderBy('date')
            ->orderBy('id') // Pour un ordre stable
            ->get();
    }

    /**
     * Calcule le solde d'un compte à une date donnée.
     * Utile pour le Grand Livre.
     *
     * @param Company $company
     * @param ComptaAccount $account
     * @param Carbon $date
     * @return float
     */
    public function getAccountBalanceAtDate(Company $company, ComptaAccount $account, Carbon $date): float
    {
        $entries = ComptaEntry::where('company_id', $company->id)
            ->where('account_id', $account->id)
            ->where('date', '<=', $date)
            ->get();

        $debit = $entries->sum('debit');
        $credit = $entries->sum('credit');

        return $debit - $credit;
    }
}
