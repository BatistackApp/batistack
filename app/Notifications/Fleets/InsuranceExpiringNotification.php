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

    public function __construct(public Insurance $insurance, public int $daysRemaining)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $fleetName = $this->insurance->fleet->name ?? 'Actif Inconnu';
        $subject = "Alerte : Assurance Flotte Expirant dans {$this->daysRemaining} jours";

        return (new MailMessage)
            ->subject($subject)
            ->greeting('Bonjour,')
            ->line("L'assurance du véhicule/engin **{$fleetName}** est sur le point d'expirer.")
            ->line("Contrat : **{$this->insurance->contract_number}**")
            ->line("Date d'expiration : **{$this->insurance->end_date->format('d/m/Y')}**")
            ->line("Jours restants : **{$this->daysRemaining} jours**")
            ->action('Voir le contrat dans Batistack', url('/flotte/' . $this->insurance->fleet_id . '/assurances'))
            ->line("Veuillez prendre les dispositions nécessaires pour le renouvellement.");
    }

    public function toDatabase($notifiable): array
    {
        $fleetName = $this->insurance->fleet->name ?? 'Actif Inconnu';
        return \Filament\Notifications\Notification::make()
            ->title("Assurance {$fleetName} expire dans {$this->daysRemaining} jours")
            ->body("Le contrat d'assurance N°{$this->insurance->contract_number} arrive à échéance le {$this->insurance->end_date->format('d/m/Y')}.")
            ->danger()
            ->icon('heroicon-o-exclamation-circle')
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
