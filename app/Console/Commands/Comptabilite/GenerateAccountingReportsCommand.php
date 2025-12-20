<?php

namespace App\Console\Commands\Comptabilite;

use App\Models\Comptabilite\ComptaAccount;
use App\Models\Comptabilite\ComptaJournal;
use App\Models\Core\Company;
use App\Services\Comptabilite\ComptaReportingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use League\Csv\Writer;
use Illuminate\Support\Facades\Storage;

class GenerateAccountingReportsCommand extends Command
{
    protected $signature = 'compta:generate-reports {--month=} {--year=} {--company=}';

    protected $description = 'Generates accounting reports (journals, general ledger) in CSV format for a given period.';

    public function __construct(private ComptaReportingService $reportingService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $month = $this->option('month') ?? now()->month;
        $year = $this->option('year') ?? now()->year;
        $companyId = $this->option('company');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $this->info("Generating accounting reports for {$startDate->format('F Y')}...");

        $companies = $companyId ? Company::where('id', $companyId)->get() : Company::all();

        foreach ($companies as $company) {
            $this->info("Processing company: {$company->name} (#{$company->id})");
            $this->generateJournalReports($company, $startDate, $endDate);
            $this->generateConsolidatedGeneralLedgerReport($company, $startDate, $endDate);
        }

        $this->info('Accounting reports generated successfully.');
    }

    private function generateJournalReports(Company $company, Carbon $startDate, Carbon $endDate): void
    {
        $journals = ComptaJournal::where('company_id', $company->id)->get();

        foreach ($journals as $journal) {
            $entries = $this->reportingService->getJournalEntries($company, $journal, $startDate, $endDate);

            if ($entries->isEmpty()) {
                continue;
            }

            $this->line("-> Generating report for journal '{$journal->name}'...");

            $csv = Writer::createFromString('');
            $csv->setDelimiter(';');
            $csv->insertOne(['Date', 'Compte', 'Libellé Compte', 'Tiers', 'Pièce', 'Libellé Écriture', 'Débit', 'Crédit']);

            foreach ($entries as $entry) {
                $csv->insertOne([
                    $entry->date->format('d/m/Y'),
                    $entry->account->number,
                    $entry->account->name,
                    $entry->tier ? $entry->tier->name : '',
                    $entry->piece_reference ?? '', // Ajout de la référence pièce
                    $entry->label,
                    number_format($entry->debit, 2, ',', ''),
                    number_format($entry->credit, 2, ',', ''),
                ]);
            }

            $fileName = "journal_{$journal->code}_{$startDate->format('Y-m')}.csv";
            $filePath = "reports/compta/{$company->id}/{$startDate->format('Y/m')}/journals/{$fileName}";
            Storage::put($filePath, $csv->toString());
        }
    }

    private function generateConsolidatedGeneralLedgerReport(Company $company, Carbon $startDate, Carbon $endDate): void
    {
        $this->line("-> Generating Consolidated General Ledger report...");

        $csv = Writer::createFromString('');
        $csv->setDelimiter(';');
        $csv->insertOne(['Compte', 'Libellé Compte', 'Date', 'Journal', 'Pièce', 'Libellé Écriture', 'Débit', 'Crédit', 'Solde']);

        // On récupère tous les comptes qui ont eu des mouvements dans la période, triés par numéro
        $accounts = ComptaAccount::where('company_id', $company->id)
            ->whereHas('entries', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            })
            ->orderBy('number')
            ->get();

        foreach ($accounts as $account) {
            $entries = $this->reportingService->getGeneralLedgerEntries($company, $account, $startDate, $endDate);

            if ($entries->isEmpty()) {
                continue;
            }

            // Calcul du solde initial au début de la période
            $initialBalance = $this->reportingService->getAccountBalanceAtDate($company, $account, $startDate->copy()->subDay());

            // Ligne de solde initial (Optionnel mais recommandé)
            $csv->insertOne([
                $account->number,
                $account->name,
                $startDate->format('d/m/Y'),
                'AN', // A-Nouveau ou Report
                '',
                'Solde au ' . $startDate->format('d/m/Y'),
                $initialBalance > 0 ? number_format($initialBalance, 2, ',', '') : '',
                $initialBalance < 0 ? number_format(abs($initialBalance), 2, ',', '') : '',
                number_format($initialBalance, 2, ',', '')
            ]);

            $runningBalance = $initialBalance;

            foreach ($entries as $entry) {
                $runningBalance += $entry->debit - $entry->credit;
                $csv->insertOne([
                    $account->number,
                    $account->name,
                    $entry->date->format('d/m/Y'),
                    $entry->journal->code,
                    $entry->piece_reference ?? '',
                    $entry->label,
                    number_format($entry->debit, 2, ',', ''),
                    number_format($entry->credit, 2, ',', ''),
                    number_format($runningBalance, 2, ',', ''),
                ]);
            }

            // Ligne vide pour séparer les comptes visuellement (optionnel)
            $csv->insertOne([]);
        }

        $fileName = "grand-livre_GLOBAL_{$startDate->format('Y-m')}.csv";
        $filePath = "reports/compta/{$company->id}/{$startDate->format('Y/m')}/general-ledger/{$fileName}";
        Storage::put($filePath, $csv->toString());
    }
}
