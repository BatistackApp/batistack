<?php

namespace App\Notifications\NoteFrais;

use App\Models\NoteFrais\Expense;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ExpenseSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Expense $expense)
    {
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title('Note de frais à valider')
            ->body("{$this->expense->employee->full_name} a déclaré : {$this->expense->amount_ttc}€ ({$this->expense->label})")
            ->icon('heroicon-o-document-currency-euro')
            ->actions([
                Action::make('review')
                    ->label('Examiner')
                    ->url("/admin/expenses/{$this->expense->id}/edit") // Adapter l'URL
                    ->button(),
            ])
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
