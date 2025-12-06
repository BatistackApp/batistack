<?php

namespace App\Notifications\RH;

use App\Models\RH\Employee;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MissingTimesheetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Employee $employee,
        public string $dateMissing,
    )
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Pointage manquant : {$this->employee->full_name}")
            ->line("L'employé {$this->employee->full_name} n'a pas saisi ses heures pour la journée du {$this->dateMissing}.")
            ->action('Saisir les heures', url('/admin/timesheets')) // URL Filament
            ->line('Merci de régulariser la situation.');
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title('Pointage manquant')
            ->body("{$this->employee->full_name} n'a pas pointé le {$this->dateMissing}.")
            ->warning()
            ->actions([
                Action::make('fill')
                    ->label('Saisir')
                    ->url('/admin/timesheets')
                    ->button(),
            ])
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
