<?php

namespace App\Notifications\Core;

use App\Models\Core\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialEndingSoonNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Subscription $subscription
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $daysRemaining = now()->diffInDays($this->subscription->trial_ends_at, false);

        return (new MailMessage)
                    ->subject("Votre période d'essai Batistack se termine bientôt")
                    ->line("Votre période d'essai pour le plan '{$this->subscription->plan->name}' se termine dans {$daysRemaining} jours.")
                    ->line("Pour continuer à utiliser nos services sans interruption, veuillez ajouter une méthode de paiement.")
                    ->action('Gérer mon abonnement', url('/app/settings/billing')) // URL à adapter
                    ->line("Merci d'utiliser Batistack !");
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
