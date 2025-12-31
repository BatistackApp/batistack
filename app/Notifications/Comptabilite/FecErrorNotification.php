<?php

namespace App\Notifications\Comptabilite;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FecErrorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public array $errors
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Erreur lors de la génération du FEC')
            ->greeting('Bonjour,')
            ->line('La génération de votre fichier FEC a échoué pour les raisons suivantes :');

        foreach ($this->errors as $error) {
            $mail->line("- " . $error);
        }

        $mail->line('Merci de corriger ces erreurs avant de relancer l\'export.');

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Erreur Génération FEC',
            'body' => 'La génération a échoué. ' . count($this->errors) . ' erreur(s) détectée(s).',
            'errors' => $this->errors,
        ];
    }
}
