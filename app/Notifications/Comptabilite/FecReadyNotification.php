<?php

namespace App\Notifications\Comptabilite;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FecReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $filePath) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre fichier FEC est prêt')
            ->line('L\'export de vos écritures comptables (FEC) a été généré avec succès.')
            // En production, générer une URL signée temporaire pour le téléchargement
            ->action('Télécharger le FEC', url('/admin/compta/exports'))
            ->line('Ce fichier est conforme aux normes de l\'administration fiscale.');
    }

    public function toDatabase($notifiable): array
    {
        return \Filament\Notifications\Notification::make()
            ->title("Votre fichier FEC est prêt")
            ->body("L\'export de vos écritures comptables (FEC) a été généré avec succès.<br>Ce fichier est conforme aux normes de l\'administration fiscale.")
            ->success()
            ->icon(Heroicon::CheckCircle)
            ->actions([
                Action::make('view')
                    ->label('Télécharger le FEC')
                    ->url(url('/admin/compta/exports'))
                    ->button()
            ])
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
