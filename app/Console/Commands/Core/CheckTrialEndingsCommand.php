<?php

namespace App\Console\Commands\Core;

use App\Models\Core\Subscription;
use App\Notifications\Core\TrialEndingSoonNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class CheckTrialEndingsCommand extends Command
{
    protected $signature = 'subscription:check-trials';

    protected $description = 'Vérifie les périodes d\'essai arrivant à expiration et notifie les utilisateurs.';

    public function handle(): void
    {
        $this->info('Vérification des périodes d\'essai...');

        // 1. Notifier les essais se terminant dans 3 jours
        $this->notifyEndingTrials();

        // 2. Gérer les essais qui ont expiré hier
        $this->handleExpiredTrials();

        $this->info('Vérification terminée.');
    }

    private function notifyEndingTrials(): void
    {
        $notificationDate = now()->addDays(3)->endOfDay();

        $subscriptionsToNotify = Subscription::query()
            ->where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', $notificationDate)
            ->whereNull('trial_notified_at')
            ->with('company.users')
            ->get();

        if ($subscriptionsToNotify->isNotEmpty()) {
            $this->line("-> Envoi de {$subscriptionsToNotify->count()} notifications de fin d'essai...");
            foreach ($subscriptionsToNotify as $subscription) {
                $admins = $subscription->company->users()->where('is_company_admin', true)->get();
                if ($admins->isNotEmpty()) {
                    Notification::send($admins, new TrialEndingSoonNotification($subscription));
                    $subscription->update(['trial_notified_at' => now()]);
                }
            }
        }
    }

    private function handleExpiredTrials(): void
    {
        $expiredSubscriptions = Subscription::query()
            ->where('status', 'trialing')
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<', now())
            ->get();

        if ($expiredSubscriptions->isNotEmpty()) {
            $this->line("-> Traitement de {$expiredSubscriptions->count()} essais expirés...");
            foreach ($expiredSubscriptions as $subscription) {
                // Dans un cas réel, on vérifierait la présence d'un moyen de paiement.
                // Ici, on simule : si pas de stripe_id, on annule.
                if (empty($subscription->stripe_id)) {
                    $subscription->update([
                        'status' => 'cancelled',
                        'ends_at' => $subscription->trial_ends_at,
                    ]);
                    $this->warn("   - L'abonnement #{$subscription->id} a été annulé (pas de moyen de paiement).");
                } else {
                    // Si un moyen de paiement existe, on le passe en 'active'
                    $subscription->update([
                        'status' => 'active',
                        'trial_ends_at' => null, // On supprime la date de fin d'essai
                    ]);
                    $this->info("   - L'abonnement #{$subscription->id} est maintenant actif.");
                }
            }
        }
    }
}
