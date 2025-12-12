<?php

namespace App\Notifications\Fleets;

use App\Models\Fleets\Maintenance;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Maintenance $maintenance, public string $alertMessage)
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
                    ->subject("Alerte Maintenance : {$this->maintenance->fleet->name} - {$this->maintenance->type->getLabel()}")
                    ->line("Une maintenance pour le véhicule '{$this->maintenance->fleet->name}' est à prévoir prochainement.")
                    ->line("Type de maintenance : {$this->maintenance->type->getLabel()}")
                    ->line("Détails : {$this->alertMessage}")
                    ->action('Voir la Maintenance', url('/')) // TODO: Mettre l'URL Filament correcte
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
            'maintenance_id' => $this->maintenance->id,
            'fleet_name' => $this->maintenance->fleet->name,
            'maintenance_type' => $this->maintenance->type->getLabel(),
            'alert_message' => $this->alertMessage,
            'message' => "Alerte maintenance pour le véhicule {$this->maintenance->fleet->name} : {$this->alertMessage}",
        ];
    }
}
