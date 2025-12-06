<?php

namespace App\Notifications\NoteFrais;

use App\Enums\NoteFrais\ExpenseStatus;
use App\Models\NoteFrais\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Expense $expense)
    {
    }

    public function via($notifiable): array
    {
        return $this->expense->status === ExpenseStatus::Rejected ? ['mail', 'database'] : ['database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Note de frais refusée')
            ->greeting("Bonjour {$notifiable->first_name},")
            ->line("Votre note de frais \"{$this->expense->label}\" de {$this->expense->amount_ttc}€ a été refusée.")
            ->line("Motif : " . ($this->expense->rejection_reason ?? 'Non spécifié'))
            ->action('Voir la note', url("/admin/expenses/{$this->expense->id}"));
    }

    public function toDatabase($notifiable): array
    {
        $statusLabel = $this->expense->status->getLabel();
        $color = $this->expense->status->getColor(); // 'success' ou 'danger'

        return \Filament\Notifications\Notification::make()
            ->title("Note de frais {$statusLabel}")
            ->body("Votre dépense \"{$this->expense->label}\" a été traitée.")
            ->status($color)
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
