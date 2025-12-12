<?php

namespace App\Console\Commands\Comptabilite;

use App\Enums\Comptabilite\JournalType;
use App\Models\Comptabilite\ComptaAccount;
use App\Models\Comptabilite\ComptaJournal;
use App\Models\Core\Company;
use App\Services\Comptabilite\ComptaReportingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;

class GenerateAccountingReportsCommand extends Command
{
    protected $signature = 'compta:generate-reports {companyId?} {--month=} {--year=}';
    protected $description = 'Génère les rapports comptables (journaux, grand livre) pour une période donnée.';

    public function handle(ComptaReportingService $reportingService): void
    {
        $companyId = $this->argument('companyId');
        $month = $this->option('month');
        $year = $this->option('year');

        // Déterminer la compagnie
        if ($companyId) {
            $company = Company::find($companyId);
            if (!$company) {
                $this->error("Compagnie avec l'ID {$companyId} non trouvée.");
                return;
            }
            $companies = collect([$company]);
        } else {
            $companies = Company::all();
        }

        if ($companies->isEmpty()) {
            $this->info("Aucune compagnie trouvée pour générer les rapports.");
            return;
        }

        foreach ($companies as $company) {
            $this->info("Génération des rapports pour la compagnie : {$company->name} (ID: {$company->id})");

            // Déterminer la période
            $targetDate = Carbon::now();
            if ($year) {
                $targetDate->setYear($year);
            }
            if ($month) {
                $targetDate->setMonth($month);
            }

            $startDate = $targetDate->copy()->startOfMonth();
            $endDate = $targetDate->copy()->endOfMonth();

            $this->info("Période : du {$startDate->format('d/m/Y')} au {$endDate->format('d/m/Y')}");

            $basePath = "compta_reports/{$company->id}/{$targetDate->format('Y-m')}";
            Storage::disk('local')->makeDirectory($basePath);

            // 1. Génération des Journaux
            $this->generateJournals($company, $startDate, $endDate, $reportingService, $basePath);

            // 2. Génération du Grand Livre
            $this->generateGeneralLedger($company, $startDate, $endDate, $reportingService, $basePath);

            $this->info("Rapports générés pour {$company->name}.");
        }

        $this->info("Processus de génération des rapports comptables terminé.");
    }

    private function generateJournals(Company $company, Carbon $startDate, Carbon $endDate, ComptaReportingService $reportingService, string $basePath): void
    {
        $journals = ComptaJournal::where('company_id', $company->id)->get();

        foreach ($journals as $journal) {
            $this->comment("  Génération du journal : {$journal->getLabel()} ({$journal->type->value})");
            $entries = $reportingService->getJournalEntries($company, $journal, $startDate, $endDate);

            if ($entries->isEmpty()) {
                $this->info("    Aucune écriture pour ce journal.");
                continue;
            }

            $filename = "journal_{$journal->type->value}_{$startDate->format('Y-m')}.csv";
            $filePath = "{$basePath}/{$filename}";

            $csv = Writer::createFromString('');
            $csv->insertOne(['Date', 'Référence', 'Compte', 'Libellé Compte', 'Tiers', 'Libellé', 'Débit', 'Crédit']);

            foreach ($entries as $entry) {
                $csv->insertOne([
                    $entry->date->format('Y-m-d'),
                    $entry->journal->type->value . '-' . $entry->id, // Exemple de référence
                    $entry->account->number,
                    $entry->account->label,
                    $entry->tier->name ?? '',
                    $entry->label,
                    $entry->debit,
                    $entry->credit,
                ]);
            }
            Storage::disk('local')->put($filePath, $csv->toString());
            $this->info("    Fichier créé : {$filePath}");
        }
    }

    private function generateGeneralLedger(Company $company, Carbon $startDate, Carbon $endDate, ComptaReportingService $reportingService, string $basePath): void
    {
        $accounts = ComptaAccount::where('company_id', $company->id)->get();

        foreach ($accounts as $account) {
            $this->comment("  Génération du Grand Livre pour le compte : {$account->number} - {$account->label}");
            $entries = $reportingService->getGeneralLedgerEntries($company, $account, $startDate, $endDate);

            if ($entries->isEmpty()) {
                $this->info("    Aucune écriture pour ce compte.");
                continue;
            }

            $filename = "grand_livre_{$account->number}_{$startDate->format('Y-m')}.csv";
            $filePath = "{$basePath}/{$filename}";

            $csv = Writer::createFromString('');
            $csv->insertOne(['Date', 'Journal', 'Tiers', 'Libellé', 'Débit', 'Crédit', 'Solde Cumulé']);

            $balance = $reportingService->getAccountBalanceAtDate($company, $account, $startDate->copy()->subDay()); // Solde au début de période
            $this->info("    Solde initial au {$startDate->copy()->subDay()->format('d/m/Y')} : {$balance}");

            $data = [];
            foreach ($entries as $entry) {
                $balance += $entry->debit - $entry->credit;
                $data[] = [
                    $entry->date->format('Y-m-d'),
                    $entry->journal->type->value,
                    $entry->tier->name ?? '',
                    $entry->label,
                    $entry->debit,
                    $entry->credit,
                    $balance,
                ];
            }
            $csv->insertAll($data);
            Storage::disk('local')->put($filePath, $csv->toString());
            $this->info("    Fichier créé : {$filePath}");
        }
    }
}
