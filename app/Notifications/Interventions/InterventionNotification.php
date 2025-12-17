<?php

namespace App\Notifications\Interventions;

use App\Models\Interventions\Intervention;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InterventionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Intervention $intervention, public string $type)
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
        $subject = match ($this->type) {
            'created' => "Nouvelle Intervention Assignée : {$this->intervention->title}",
            'status_changed' => "Statut Intervention Modifié : {$this->intervention->title}",
            'deleted' => "Intervention Annulée : {$this->intervention->title}",
            default => "Notification Intervention : {$this->intervention->title}",
        };

        $messageLine = match ($this->type) {
            'created' => "Une nouvelle intervention vous a été assignée : '{$this->intervention->title}'.",
            'status_changed' => "Le statut de l'intervention '{$this->intervention->title}' est passé à '{$this->intervention->status->getLabel()}'.",
            'deleted' => "L'intervention '{$this->intervention->title}' a été annulée.",
            default => "Une mise à jour a eu lieu concernant l'intervention '{$this->intervention->title}'.",
        };

        return (new MailMessage)
                    ->subject($subject)
                    ->line($messageLine)
                    ->line("Client : {$this->intervention->client->name}")
                    ->line("Chantier : {$this->intervention->chantier->name ?? 'N/A'}")
                    ->line("Date prévue : {$this->intervention->planned_start_date->format('d/m/Y')}")
                    ->action('Voir l\'Intervention', url('/')) // TODO: Mettre l'URL Filament correcte
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
            'intervention_id' => $this->intervention->id,
            'title' => $this->intervention->title,
            'status' => $this->intervention->status->value,
            'type' => $this->type,
            'message' => "Notification pour l'intervention '{$this->intervention->title}' ({$this->type}).",
        ];
    }
}
