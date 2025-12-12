<?php

namespace App\Console\Commands\Fleets;

use App\Enums\UserRole;
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

        // 1. Trouver les contrats expirant bientôt et n'ayant pas déjà été notifiés
        $expiringInsurances = Insurance::whereBetween('end_date', [$startDate, $alertDate])
            ->whereNull('notified_at') // On ne notifie qu'une seule fois
            ->where('is_active', true)
            ->with('fleet')
            ->get();

        if ($expiringInsurances->isEmpty()) {
            $this->info('No new insurance contracts to notify for expiration.');
            return;
        }

        $this->info("Found {$expiringInsurances->count()} insurance contracts expiring soon...");

        // 2. Trouver les utilisateurs à notifier (Gestionnaires de flotte ou Admins)
        $notifiableUsers = User::where('role', UserRole::ADMINISTRATEUR)->get(); // Adapter selon vos rôles

        if ($notifiableUsers->isEmpty()) {
            $this->warn('No users found with the required role to send notifications to.');
            return;
        }

        // 3. Envoyer les notifications et marquer comme notifié
        foreach ($expiringInsurances as $insurance) {
            $daysRemaining = $startDate->diffInDays($insurance->end_date, false);

            $this->line("-> Alerting for '{$insurance->fleet->name}' (Contract: {$insurance->contract_number}). Remaining days: {$daysRemaining}.");

            // Envoi de la notification
            Notification::send($notifiableUsers, new InsuranceExpiringNotification($insurance, $daysRemaining));

            // Marquer comme notifié pour éviter les spams
            $insurance->update(['notified_at' => now()]);
        }

        $this->info('Expiration check completed.');
    }
}
