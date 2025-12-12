<?php

namespace App\Notifications\Fleets;

use App\Models\Fleets\FleetAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FleetAssignedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public FleetAssignment $assignment,
        public string $type = 'created' // 'created', 'updated', 'deleted'
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail']; // Peut être étendu à 'database', 'broadcast', etc.
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = '';
        $greeting = '';
        $line = '';

        switch ($this->type) {
            case 'created':
                $subject = "Nouvelle assignation de véhicule : {$this->assignment->fleet->name}";
                $greeting = "Bonjour {$notifiable->first_name},";
                $line = "Un véhicule vous a été assigné : **{$this->assignment->fleet->name} ({$this->assignment->fleet->registration_number})** du {$this->assignment->start_date->format('d/m/Y')} au {$this->assignment->end_date->format('d/m/Y')}.";
                break;
            case 'updated':
                $subject = "Mise à jour d'assignation de véhicule : {$this->assignment->fleet->name}";
                $greeting = "Bonjour {$notifiable->first_name},";
                $line = "L'assignation du véhicule **{$this->assignment->fleet->name} ({$this->assignment->fleet->registration_number})** a été mise à jour. Nouvelle période : du {$this->assignment->start_date->format('d/m/Y')} au {$this->assignment->end_date->format('d/m/Y')}.";
                break;
            case 'deleted':
                $subject = "Annulation d'assignation de véhicule : {$this->assignment->fleet->name}";
                $greeting = "Bonjour {$notifiable->first_name},";
                $line = "L'assignation du véhicule **{$this->assignment->fleet->name} ({$this->assignment->fleet->registration_number})** a été annulée.";
                break;
            default:
                $subject = "Information concernant une assignation de véhicule";
                $greeting = "Bonjour {$notifiable->first_name},";
                $line = "Une information concernant l'assignation du véhicule **{$this->assignment->fleet->name} ({$this->assignment->fleet->registration_number})**.";
                break;
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($line)
            ->action('Voir l\'assignation', url('/fleet-assignments/' . $this->assignment->id)) // Adapter l'URL
            ->line('Merci d\'utiliser notre application !');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'assignment_id' => $this->assignment->id,
            'fleet_name' => $this->assignment->fleet->name,
            'fleet_registration' => $this->assignment->fleet->registration_number,
            'start_date' => $this->assignment->start_date->format('Y-m-d'),
            'end_date' => $this->assignment->end_date->format('Y-m-d'),
            'type' => $this->type,
        ];
    }
}
