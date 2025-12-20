<?php

namespace App\Notifications\Locations;

use App\Models\Locations\RentalContract;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RentalContractExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public RentalContract $contract)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Fin de contrat de location : {$this->contract->reference}")
            ->line("Le contrat de location #{$this->contract->id} pour le chantier {$this->contract->chantiers->name} arrive à échéance le {$this->contract->end_date->format('d/m/Y')}.")
            ->action('Voir le contrat', url("/admin/rental-contracts/{$this->contract->id}"))
            ->line('Merci de vérifier si le matériel a été restitué ou si le contrat doit être prolongé.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'contract_id' => $this->contract->id,
            'chantier_id' => $this->contract->chantiers_id,
            'end_date' => $this->contract->end_date,
            'message' => "Le contrat de location #{$this->contract->id} expire bientôt.",
        ];
    }
}
