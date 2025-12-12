<?php

namespace App\Notifications\Fleets;

use App\Models\Fleets\FleetAssignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FleetAssignmentReminderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public FleetAssignment $assignment,
        public int $daysRemaining
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
        $subject = "Rappel : Fin prochaine de votre assignation de véhicule ({$this->assignment->fleet->name})";
        $greeting = "Bonjour {$notifiable->first_name},";
        $line1 = "Votre assignation du véhicule **{$this->assignment->fleet->name} ({$this->assignment->fleet->registration_number})** se termine dans **{$this->daysRemaining} jour(s)**, le {$this->assignment->end_date->format('d/m/Y')}.";
        $line2 = "Veuillez prendre les dispositions nécessaires.";

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($line1)
            ->line($line2)
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
            'end_date' => $this->assignment->end_date->format('Y-m-d'),
            'days_remaining' => $this->daysRemaining,
        ];
    }
}
