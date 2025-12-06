<?php

namespace App\Notifications\Facturation;

use App\Models\Facturation\SalesDocument;
use Filament\Actions\Action;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SalesDocument $document)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Retard de paiement : Facture {$this->document->reference}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("La facture {$this->document->reference} du client {$this->document->tiers->name} est arrivée à échéance le {$this->document->due_date->format('d/m/Y')}.")
            ->line("Montant à recouvrer : " . number_format($this->document->total_ttc, 2) . " €")
            ->action('Voir la facture', url("/admin/sales-documents/{$this->document->id}")) // Adapte l'URL selon tes routes
            ->line('Merci de faire le nécessaire.');
    }

    public function toDatabase($notifiable): array
    {
        // Format spécifique pour que Filament l'affiche joliment
        return \Filament\Notifications\Notification::make()
            ->title('Facture en retard')
            ->body("Le client {$this->document->tier->name} n'a pas réglé la facture {$this->document->reference}.")
            ->danger() // Couleur Rouge
            ->icon('heroicon-o-exclamation-circle')
            ->actions([
                Action::make('view')
                    ->label('Voir')
                    ->url("/admin/sales-documents/{$this->document->id}")
                    ->button(),
            ])
            ->getDatabaseMessage();
    }

    public function toArray($notifiable): array
    {
        return [];
    }
}
