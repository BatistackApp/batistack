<?php

namespace App\Console\Commands\Fleets;

use App\Models\Fleets\FleetAssignment;
use App\Notifications\Fleets\FleetAssignmentReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class CheckFleetAssignmentRemindersCommand extends Command
{
    protected $signature = 'fleet:check-assignment-reminders';
    protected $description = 'Vérifie les assignations de flotte dont la fin approche et envoie des rappels.';

    public function handle(): void
    {
        $this->info('Vérification des rappels d\'assignation de flotte...');

        $remindDays = [7, 3, 1]; // Envoyer des rappels 7, 3 et 1 jour(s) avant la fin

        foreach ($remindDays as $days) {
            $targetDate = Carbon::today()->addDays($days);

            $assignments = FleetAssignment::where('end_date', $targetDate)
                ->where('status', 'active') // Seulement les assignations actives
                ->where(function ($query) use ($days) {
                    // S'assurer que la notification n'a pas déjà été envoyée pour ce rappel spécifique
                    $query->whereNull('notified_at')
                          ->orWhere('notified_at', '<', Carbon::today()->subDays($days)); // Ou si la dernière notif est plus ancienne que le rappel
                })
                ->with(['fleet', 'assignable'])
                ->get();

            if ($assignments->isEmpty()) {
                $this->info("Aucune assignation à rappeler pour {$days} jour(s).");
                continue;
            }

            $this->comment("Envoi de rappels pour les assignations se terminant dans {$days} jour(s)...");

            foreach ($assignments as $assignment) {
                $assignable = $assignment->assignable;

                if ($assignable) {
                    try {
                        if (method_exists($assignable, 'notify')) {
                            $assignable->notify(new FleetAssignmentReminderNotification($assignment, $days));
                        } elseif ($assignable instanceof \App\Models\RH\Team) {
                            foreach ($assignable->employees as $employee) {
                                $employee->notify(new FleetAssignmentReminderNotification($assignment, $days));
                            }
                        }
                        $assignment->updateQuietly(['notified_at' => Carbon::now()]); // Marquer comme notifié
                        $this->info("  Rappel envoyé pour l'assignation {$assignment->id} ({$assignment->fleet->registration_number}) à {$assignable->name}.");
                    } catch (\Exception $e) {
                        $this->error("  Erreur lors de l'envoi du rappel pour l'assignation {$assignment->id}: " . $e->getMessage());
                    }
                }
            }
        }

        $this->info('Vérification des rappels d\'assignation de flotte terminée.');
    }
}
