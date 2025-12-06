<?php

namespace App\Console\Commands\RH;

use App\Models\RH\Employee;
use App\Notifications\RH\MissingTimesheetNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class CheckMissingTimesheetsCommand extends Command
{
    protected $signature = 'rh:check-timesheets';

    protected $description = 'Vérifie les pointages manquants de la veille';

    public function handle(): void
    {
        // 1. Déterminer la date à vérifier (Hier)
        $dateToCheck = Carbon::yesterday();

        // Si hier était un Samedi ou Dimanche, on ne vérifie pas (sauf config spécifique)
        if ($dateToCheck->isSunday()) {
            $this->info("Hier était un weekend, pas de vérification.");
            return;
        }

        $dateString = $dateToCheck->toDateString();
        $this->info("Vérification des pointages pour le {$dateString}...");

        // 2. Récupérer les employés actifs
        // On optimise avec 'whereDoesntHave' pour trouver ceux qui n'ont PAS de timesheet
        $employeesWithoutLogs = Employee::query()
            ->where('is_active', true)
            ->whereDoesntHave('timesheets', function ($query) use ($dateString) {
                $query->where('date', $dateString);
            })
            // On charge l'user lié et le chef d'équipe (via les équipes) pour notifier
            ->with(['user', 'teams.leader.user'])
            ->get();

        $notificationCount = 0;

        foreach ($employeesWithoutLogs as $employee) {

            // QUI notifier ?
            $recipients = collect();

            // A. L'employé lui-même s'il a un accès User
            if ($employee->user) {
                $recipients->push($employee->user);
            }

            // B. Son Chef d'équipe (S'il est dans une équipe avec un chef défini)
            foreach ($employee->teams as $team) {
                if ($team->leader && $team->leader->user) {
                    $recipients->push($team->leader->user);
                }
            }

            // C. Si personne (pas d'user, pas de chef), on notifie les Admins du tenant
            if ($recipients->isEmpty()) {
                // Supposons une méthode helper ou un scope pour les admins
                // $recipients = User::where('company_id', $employee->company_id)->where('is_admin', true)->get();
            }

            // Envoi groupé (unique() pour éviter de spammer si le chef est aussi l'employé)
            if ($recipients->isNotEmpty()) {
                Notification::send(
                    $recipients->unique('id'),
                    new MissingTimesheetNotification($employee, $dateToCheck->format('d/m/Y'))
                );
                $notificationCount++;
            }
        }

        $this->info("Vérification terminée. {$notificationCount} notifications envoyées.");
    }
}
