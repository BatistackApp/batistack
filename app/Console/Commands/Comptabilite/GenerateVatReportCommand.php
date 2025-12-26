<?php

namespace App\Console\Commands\Comptabilite;

use App\Models\Comptabilite\ComptaEntry;
use App\Models\Core\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class GenerateVatReportCommand extends Command
{
    protected $signature = 'compta:generate-vat-report {--month=} {--year=} {--company=}';

    protected $description = 'Génère un rapport de TVA (collectée vs déductible) pour une période donnée.';

    public function handle(): void
    {
        $month = $this->option('month') ?? now()->subMonth()->month;
        $year = $this->option('year') ?? now()->year;
        $companyId = $this->option('company');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $this->info("Génération du rapport de TVA pour la période du {$startDate->format('d/m/Y')} au {$endDate->format('d/m/Y')}.");

        $companies = $companyId ? Company::where('id', $companyId)->get() : Company::all();

        $headers = ['Compagnie', 'TVA Collectée', 'TVA Déductible', 'TVA à Payer / Crédit'];
        $rows = [];

        foreach ($companies as $company) {
            $this->line("Traitement de la compagnie: {$company->name}");

            // TVA Collectée (Produits) - Classe 7, Comptes 4457xx
            $collectedVat = ComptaEntry::query()
                ->forCompany($company)
                ->whereBetween('date', [$startDate, $endDate])
                ->whereHas('account', fn ($q) => $q->where('number', 'like', '4457%'))
                ->sum('credit'); // La TVA collectée est au crédit

            // TVA Déductible (Charges) - Classe 6, Comptes 4456xx
            $deductibleVat = ComptaEntry::query()
                ->forCompany($company)
                ->whereBetween('date', [$startDate, $endDate])
                ->whereHas('account', fn ($q) => $q->where('number', 'like', '4456%'))
                ->sum('debit'); // La TVA déductible est au débit

            $vatToPay = $collectedVat - $deductibleVat;

            $rows[] = [
                'Compagnie' => $company->name,
                'TVA Collectée' => number_format($collectedVat, 2, ',', ' ') . ' €',
                'TVA Déductible' => number_format($deductibleVat, 2, ',', ' ') . ' €',
                'TVA à Payer / Crédit' => number_format($vatToPay, 2, ',', ' ') . ' €',
            ];
        }

        $this->table($headers, $rows);
        $this->info('Rapport de TVA terminé.');
    }
}
