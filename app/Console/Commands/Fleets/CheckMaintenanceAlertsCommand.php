<?php

namespace App\Console\Commands\Fleets;

use App\Enums\UserRole;
use App\Models\Fleets\Maintenance;
use App\Models\User;
use App\Notifications\Fleets\MaintenanceAlertNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class CheckMaintenanceAlertsCommand extends Command
{
    protected $signature = 'fleet:check-maintenance-alerts';

    protected $description = 'Checks for upcoming fleet maintenances and sends alerts.';

    public function handle(): void
    {
        $dateThreshold = Carbon::now()->addDays(30); // Alerte si la maintenance est dans moins de 30 jours
        $mileageThreshold = 5000; // Alerte si la maintenance est dans moins de 5000 km

        // 1. Trouver les maintenances à venir qui n'ont pas encore été notifiées
        $upcomingMaintenances = Maintenance::whereNull('notified_at')
            ->where(function ($query) use ($dateThreshold, $mileageThreshold) {
                // Maintenance basée sur la date
                $query->whereNotNull('next_date')
                    ->where('next_date', '<=', $dateThreshold);

                // OU Maintenance basée sur le kilométrage (si le véhicule a un kilométrage actuel)
                $query->orWhere(function ($query) use ($mileageThreshold) {
                    $query->whereNotNull('next_mileage')
                        ->whereHas('fleet', function ($q) use ($mileageThreshold) {
                            $q->whereRaw('fleets.mileage + ? >= maintenances.next_mileage', [$mileageThreshold]);
                        });
                });
            })
            ->with('fleet')
            ->get();

        if ($upcomingMaintenances->isEmpty()) {
            $this->info('No new upcoming maintenances to notify.');
            return;
        }

        $this->info("Found {$upcomingMaintenances->count()} upcoming maintenances to alert for...");

        // 2. Trouver les utilisateurs à notifier (Gestionnaires de flotte ou Admins)
        $notifiableUsers = User::where('role', UserRole::ADMINISTRATEUR)->get(); // Adapter selon vos rôles

        if ($notifiableUsers->isEmpty()) {
            $this->warn('No users found with the required role to send notifications to.');
            return;
        }

        // 3. Envoyer les notifications et marquer comme notifié
        foreach ($upcomingMaintenances as $maintenance) {
            $alertMessage = $this->getAlertMessage($maintenance);
            $this->line("-> Alerting for '{$maintenance->fleet->name}' ({$maintenance->type->getLabel()}). {$alertMessage}");

            Notification::send($notifiableUsers, new MaintenanceAlertNotification($maintenance, $alertMessage));

            // Marquer comme notifié pour éviter les spams
            $maintenance->update(['notified_at' => now()]);
        }

        $this->info('Maintenance alerts check completed.');
    }

    /**
     * Génère un message d'alerte basé sur le type d'échéance.
     */
    protected function getAlertMessage(Maintenance $maintenance): string
    {
        $messages = [];
        if ($maintenance->next_date && $maintenance->next_date->isFuture()) {
            $daysRemaining = Carbon::now()->diffInDays($maintenance->next_date, false);
            $messages[] = "Échéance par date dans {$daysRemaining} jours ({$maintenance->next_date->format('d/m/Y')}).";
        }
        if ($maintenance->next_mileage && $maintenance->fleet->mileage < $maintenance->next_mileage) {
            $kmRemaining = $maintenance->next_mileage - $maintenance->fleet->mileage;
            $messages[] = "Échéance par kilométrage dans {$kmRemaining} km (à {$maintenance->next_mileage} km).";
        }

        return implode(' et ', $messages);
    }
}
