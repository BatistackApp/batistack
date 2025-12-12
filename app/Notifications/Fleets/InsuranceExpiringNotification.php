<?php

namespace App\Notifications\Fleets;

use App\Models\Fleets\Insurance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InsuranceExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Insurance $insurance, public int $daysRemaining)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database']; // Envoi par email et stockage en BDD
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject("Alerte Expiration d'Assurance : {$this->insurance->fleet->name}")
                    ->line("Le contrat d'assurance n°{$this->insurance->contract_number} pour le véhicule '{$this->insurance->fleet->name}' arrive à expiration.")
                    ->line("Date d'expiration : {$this->insurance->end_date->format('d/m/Y')} ({$this->daysRemaining} jours restants).")
                    ->action('Voir le Véhicule', url('/')) // TODO: Mettre l'URL Filament correcte
                    ->line('Merci de prendre les mesures nécessaires.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'insurance_id' => $this->insurance->id,
            'fleet_name' => $this->insurance->fleet->name,
            'end_date' => $this->insurance->end_date->format('d/m/Y'),
            'days_remaining' => $this->daysRemaining,
            'message' => "L'assurance pour le véhicule {$this->insurance->fleet->name} expire dans {$this->daysRemaining} jours.",
        ];
    }
}
