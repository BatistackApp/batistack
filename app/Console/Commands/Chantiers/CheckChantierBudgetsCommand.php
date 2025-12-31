<?php

namespace App\Console\Commands\Chantiers;

use App\Enums\Chantiers\ChantiersStatus;
use App\Enums\UserRole;
use App\Models\Chantiers\Chantiers;
use App\Models\User;
use App\Notifications\Chantiers\BudgetAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckChantierBudgetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chantiers:check-budgets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie la consommation budgétaire des chantiers et envoie des alertes.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Début de la vérification des budgets chantiers...');

        // On récupère les chantiers actifs qui ont un budget défini (> 0)
        $chantiers = Chantiers::query()
            ->whereNotIn('status', [ChantiersStatus::COMPLETED, ChantiersStatus::CANCELLED])
            ->get()
            ->filter(function (Chantiers $chantier) {
                return $chantier->total_budgeted_cost > 0;
            });

        $count = 0;

        foreach ($chantiers as $chantier) {
            $budget = $chantier->total_budgeted_cost;
            $real = $chantier->total_real_cost;
            $percentage = ($real / $budget) * 100;
            $lastAlertLevel = $chantier->last_budget_alert_level ?? 0;

            $newAlertLevel = 0;

            // Définition des seuils
            if ($percentage >= 100) {
                $newAlertLevel = 100;
            } elseif ($percentage >= 80) {
                $newAlertLevel = 80;
            }

            // Si on a franchi un nouveau seuil supérieur au précédent
            if ($newAlertLevel > $lastAlertLevel) {
                $this->sendAlert($chantier, $percentage, $real, $budget);

                // Mise à jour du niveau d'alerte pour ne pas renvoyer la même notif
                $chantier->update(['last_budget_alert_level' => $newAlertLevel]);
                $count++;
            }
        }

        $this->info("Vérification terminée. {$count} alerte(s) envoyée(s).");
    }

    protected function sendAlert(Chantiers $chantier, float $percentage, float $real, float $budget)
    {
        // Identifier les destinataires : Admins de l'entreprise et Gestionnaires de chantier
        // On suppose que le modèle Chantier a une relation 'users' ou on notifie les admins de la company

        $company = $chantier->company;

        // Récupérer les admins de l'entreprise
        $admins = User::where('company_id', $company->id)
            ->whereIn('role', [UserRole::ADMINISTRATEUR, UserRole::SUPERADMIN]) // Adapter selon les rôles réels
            ->get();

        // Ajouter le chef de chantier s'il est défini (supposons un champ 'manager_id' ou relation)
        // Pour l'instant on notifie les admins.

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new BudgetAlertNotification($chantier, round($percentage, 1), $real, $budget));
            $this->line("Alerte envoyée pour le chantier {$chantier->name} ({$percentage}%)");
        }
    }
}
