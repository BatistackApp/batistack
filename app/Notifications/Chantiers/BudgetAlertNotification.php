<?php

namespace App\Notifications\Chantiers;

use App\Models\Chantiers\Chantiers;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Chantiers $chantier,
        public float $percentageUsed, // Ex: 85.5
        public float $totalRealCost,
        public float $totalBudgetedCost
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->percentageUsed >= 100 ? 'DÉPASSÉ' : 'CRITIQUE';
        $color = $this->percentageUsed >= 100 ? 'error' : 'warning';

        return (new MailMessage)
            ->subject("Alerte Budget Chantier : {$this->chantier->name} - {$status}")
            ->greeting('Bonjour,')
            ->line("Le budget du chantier **{$this->chantier->name}** a atteint un niveau d'alerte.")
            ->line("Consommation : **{$this->percentageUsed}%** du budget prévu.")
            ->line("Coût Réel à date : " . number_format($this->totalRealCost, 2, ',', ' ') . " €")
            ->line("Budget Total : " . number_format($this->totalBudgetedCost, 2, ',', ' ') . " €")
            ->action('Voir le Chantier', url("/admin/chantiers/{$this->chantier->id}"))
            ->line('Merci de vérifier les dépenses.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => "Alerte Budget : {$this->chantier->name}",
            'body' => "Le budget est consommé à {$this->percentageUsed}%. Coût: {$this->totalRealCost}€ / {$this->totalBudgetedCost}€",
            'chantier_id' => $this->chantier->id,
            'percentage_used' => $this->percentageUsed,
            'status' => $this->percentageUsed >= 100 ? 'danger' : 'warning',
        ];
    }
}
