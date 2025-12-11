<?php

namespace App\Console\Commands\Paie;

use App\Models\Core\Company;
use App\Models\Paie\PayrollPeriods;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreatePayrollPeriodCommand extends Command
{
    protected $signature = 'payroll:create-period {--company= : ID de entreprise spécifique}';

    protected $description = 'Creates the next payroll period for all active companies.';

    public function handle()
    {
        $companyId = $this->option('company');

        $companies = $companyId ? Company::where('id', $companyId)->get() : Company::where('is_active', true)->get();

        if ($companies->isEmpty()) {
            $this->error("Aucune entreprise active n'a été trouvée pour créer des périodes de paie.");
            return Command::FAILURE;
        }

        $this->info("Création des prochaines périodes de paie pour {$companies->count()} entreprises...");

        foreach ($companies as $company) {
            DB::transaction(function () use ($company) {
                $this->createNextPeriodForCompany($company);
            });
        }

        $this->info("Création des périodes de paie terminée.");
        return Command::SUCCESS;
    }

    /**
     * Crée la prochaine période de paie pour une entreprise.
     */
    protected function createNextPeriodForCompany(Company $company): void
    {
        // On cherche la dernière période de paie pour cette compagnie.
        $lastPeriod = PayrollPeriods::where('company_id', $company->id)
            ->latest('end_date')
            ->first();

        // Si aucune période n'existe, on commence au mois précédent le mois actuel
        if (!$lastPeriod) {
            $startDate = Carbon::now()->subMonth()->startOfMonth();
            $endDate = Carbon::now()->subMonth()->endOfMonth();
        } else {
            // La nouvelle période commence le jour suivant la fin de la dernière période.
            $startDate = $lastPeriod->end_date->copy()->addDay();

            // Calculer la date de fin : Fin du mois suivant le début.
            $endDate = $startDate->copy()->endOfMonth();
        }

        // Vérification de sécurité : éviter de créer une période trop en avance
        if ($endDate->lessThanOrEqualTo(Carbon::now()->addMonth()->endOfMonth())) {

            PayrollPeriods::create([
                'company_id' => $company->id,
                'name' => 'Paie ' . $startDate->format('m/Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'draft',
            ]);
            $this->line("-> Created period '{$startDate->format('m/Y')}' for Company #{$company->id}.");
        } else {
            $this->line("-> Period for Company #{$company->id} already up to date.");
        }
    }
}
