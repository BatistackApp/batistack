<?php

namespace App\Console\Commands\Comptabilite;

use App\Jobs\Comptabilite\GenerateFecJob;
use App\Models\Core\Company;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GenerateFecCommand extends Command
{
    protected $signature = 'compta:generate-fec {companyId} {fiscalYearStart} {fiscalYearEnd} {--userId=}';
    protected $description = 'Génère le Fichier des Écritures Comptables (FEC) pour une compagnie et une période donnée.';

    public function handle(): void
    {
        $companyId = $this->argument('companyId');
        $fiscalYearStart = $this->argument('fiscalYearStart');
        $fiscalYearEnd = $this->argument('fiscalYearEnd');
        $userId = $this->option('userId');

        $company = Company::find($companyId);
        if (!$company) {
            $this->error("Compagnie avec l'ID {$companyId} non trouvée.");
            return;
        }

        $requestingUser = null;
        if ($userId) {
            $requestingUser = User::find($userId);
            if (!$requestingUser) {
                $this->warn("Utilisateur avec l'ID {$userId} non trouvé. La notification ne sera pas envoyée.");
            }
        } else {
            // Si aucun utilisateur n'est spécifié, on peut utiliser un utilisateur par défaut ou le premier admin
            // Pour cet exemple, nous allons logguer un avertissement.
            $this->warn("Aucun ID utilisateur spécifié. La notification de fin de génération du FEC ne sera pas envoyée.");
        }

        $this->info("Lancement de la génération du FEC pour la compagnie {$company->name} ({$companyId}) de {$fiscalYearStart} à {$fiscalYearEnd}...");

        try {
            GenerateFecJob::dispatch($company, $fiscalYearStart, $fiscalYearEnd, $requestingUser);
            $this->info("Le job de génération du FEC a été dispatché avec succès.");
        } catch (\Exception $e) {
            $this->error("Erreur lors du dispatch du job de génération du FEC : " . $e->getMessage());
            Log::error("Erreur lors du dispatch du job de génération du FEC : " . $e->getMessage(), [
                'company_id' => $companyId,
                'fiscal_year_start' => $fiscalYearStart,
                'fiscal_year_end' => $fiscalYearEnd,
                'user_id' => $userId,
            ]);
        }
    }
}
