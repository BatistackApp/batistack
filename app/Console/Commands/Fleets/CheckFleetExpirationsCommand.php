<?php

namespace App\Console\Commands\Fleets;

use App\Models\Fleets\Insurance;
use App\Models\User;
use App\Notifications\Fleets\InsuranceExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class CheckFleetExpirationsCommand extends Command
{
    protected $signature = 'fleet:check-expirations';

    protected $description = 'Checks for expiring fleet insurances and sends alerts.';

    public function handle(): void
    {
        $startDate = Carbon::now()->startOfDay();
        $alertDate = Carbon::now()->addDays(30)->endOfDay();

        // Trouver tous les contrats dont la date de fin est entre aujourd'hui et dans 30 jours
        $expiringInsurances = Insurance::whereBetween('end_date', [$startDate, $alertDate])
            ->where('is_expired', false) // Ajoutez un flag pour ne pas spammer après l'expiration
            ->with('fleet')
            ->get();

        if ($expiringInsurances->isEmpty()) {
            $this->info('No insurance contracts are expiring in the next 30 days.');
        }

        $this->info("Found {$expiringInsurances->count()} insurance contracts expiring soon...");

        $notifiableUsers = User::where('role', 'admin')
            ->orWhere('role', 'comptabilite')
            ->get();

        if ($notifiableUsers->isEmpty()) {
            $this->warn('No users found with the fleet_manager role to send notifications to.');
        }

        foreach ($expiringInsurances as $insurance) {
            $daysRemaining = $startDate->diffInDays($insurance->end_date, false);

            $this->line("-> Alerting for '{$insurance->fleet->name}' (Contract: {$insurance->contract_number}). Remaining days: {$daysRemaining}.");

            // Envoi de la notification
            Notification::send($notifiableUsers, new InsuranceExpiringNotification($insurance, $daysRemaining));

            // Si la date d'expiration est dépassée (daysRemaining <= 0), vous pouvez marquer le contrat
            // comme 'is_expired' => true pour stopper les alertes récurrentes.
        }

        $this->info('Expiration check completed.');

    }
}
