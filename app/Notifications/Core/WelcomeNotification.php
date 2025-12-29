<?php

namespace App\Notifications\Core;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

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
    public function toMail(User $notifiable): MailMessage
    {
        $subscription = $notifiable->company->activeSubscription;

        return (new MailMessage)
                    ->subject('Bienvenue sur Batistack !')
                    ->greeting("Bonjour {$notifiable->name},")
                    ->line("Votre entreprise, {$notifiable->company->name}, a bien été créée.")
                    ->line("Vous bénéficiez du plan '{$subscription->plan->name}'.")
                    ->lineIf($subscription->trial_ends_at, "Votre période d'essai se termine le {$subscription->trial_ends_at->format('d/m/Y')}.")
                    ->action('Accéder à votre espace', url('/'))
                    ->line("Nous sommes ravis de vous compter parmi nous !");
    }
}
