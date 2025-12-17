<?php

namespace App\Console\Commands\Comptabilite;

use App\Models\Comptabilite\ComptaAccount;
use App\Models\Comptabilite\ComptaJournal;
use App\Models\Core\Company;
use App\Services\Comptabilite\ComptaReportingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use League\Csv\Writer;
use Storage;

class GenerateAccountingReportsCommand extends Command
{
    protected $signature = 'compta:generate-reports {--month=} {--year=}';

    protected $description = 'Generates accounting reports (journals, general ledger) in CSV format for a given period.';

    public function __construct(private ComptaReportingService $reportingService)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $month = $this->option('month') ?? now()->month;
        $year = $this->option('year') ?? now()->year;

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $this->info("Generating accounting reports for {$startDate->format('F Y')}...");

        // Pour l'instant, on traite la première compagnie trouvée.
        // Dans un environnement multi-tenant, il faudrait itérer sur toutes les compagnies.
        $company = Company::first();
        if (!$company) {
            $this->error('No company found.');
            return;
        }

        $this->generateJournalReports($company, $startDate, $endDate);
        $this->generateGeneralLedgerReport($company, $startDate, $endDate);

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
            $csv->insertOne(['Date', 'Compte', 'Libellé Compte', 'Tiers', 'Libellé Écriture', 'Débit', 'Crédit']);

            foreach ($entries as $entry) {
                $csv->insertOne([
                    $entry->date->format('d/m/Y'),
                    $entry->account->number,
                    $entry->account->name,
                    $entry->tier->name ?? '',
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

    private function generateGeneralLedgerReport(Company $company, Carbon $startDate, Carbon $endDate): void
    {
        $this->line("-> Generating General Ledger report...");

        // On récupère tous les comptes qui ont eu des mouvements dans la période
        $accounts = ComptaAccount::where('company_id', $company->id)
            ->whereHas('entries', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            })
            ->get();

        foreach ($accounts as $account) {
            $entries = $this->reportingService->getGeneralLedgerEntries($company, $account, $startDate, $endDate);

            if ($entries->isEmpty()) {
                continue;
            }

            $csv = Writer::createFromString('');
            $csv->setDelimiter(';');
            $csv->insertOne(['Date', 'Journal', 'Libellé Écriture', 'Débit', 'Crédit', 'Solde']);

            // Calcul du solde initial au début de la période
            $initialBalance = $this->reportingService->getAccountBalanceAtDate($company, $account, $startDate->copy()->subDay());
            $csv->insertOne(['', '', 'Solde au ' . $startDate->format('d/m/Y'), '', '', number_format($initialBalance, 2, ',', '')]);

            $runningBalance = $initialBalance;
            foreach ($entries as $entry) {
                $runningBalance += $entry->debit - $entry->credit;
                $csv->insertOne([
                    $entry->date->format('d/m/Y'),
                    $entry->journal->code,
                    $entry->label,
                    number_format($entry->debit, 2, ',', ''),
                    number_format($entry->credit, 2, ',', ''),
                    number_format($runningBalance, 2, ',', ''),
                ]);
            }

            $fileName = "grand-livre_{$account->number}_{$startDate->format('Y-m')}.csv";
            $filePath = "reports/compta/{$company->id}/{$startDate->format('Y/m')}/general-ledger/{$fileName}";
            Storage::put($filePath, $csv->toString());
        }
    }
}
