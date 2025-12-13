<?php

namespace App\Notifications\GPAO;

use App\Models\GPAO\ProductionOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProductionOrderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public ProductionOrder $productionOrder,
        public string $type = 'created' // 'created', 'updated', 'status_changed', 'delayed'
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail']; // Peut être étendu à 'database', 'broadcast', etc.
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = '';
        $greeting = "Bonjour {$notifiable->first_name},";
        $line = '';

        switch ($this->type) {
            case 'created':
                $subject = "Nouvel Ordre de Fabrication créé : {$this->productionOrder->reference}";
                $line = "Un nouvel Ordre de Fabrication **{$this->productionOrder->reference}** a été créé pour le produit **{$this->productionOrder->product->name}** (Quantité: {$this->productionOrder->quantity}).";
                break;
            case 'updated':
                $subject = "Mise à jour d'un Ordre de Fabrication : {$this->productionOrder->reference}";
                $line = "L'Ordre de Fabrication **{$this->productionOrder->reference}** a été mis à jour. Produit: **{$this->productionOrder->product->name}** (Quantité: {$this->productionOrder->quantity}).";
                break;
            case 'status_changed':
                $subject = "Statut de l'Ordre de Fabrication {$this->productionOrder->reference} mis à jour";
                $line = "Le statut de l'Ordre de Fabrication **{$this->productionOrder->reference}** pour le produit **{$this->productionOrder->product->name}** est passé à **{$this->productionOrder->status->getLabel()}**.";
                break;
            case 'delayed':
                $subject = "Alerte de retard : Ordre de Fabrication {$this->productionOrder->reference}";
                $line = "L'Ordre de Fabrication **{$this->productionOrder->reference}** pour le produit **{$this->productionOrder->product->name}** est en retard. La date de fin planifiée était le **{$this->productionOrder->planned_end_date->format('d/m/Y')}**.";
                break;
            default:
                $subject = "Information concernant un Ordre de Fabrication";
                $line = "Une information concernant l'Ordre de Fabrication **{$this->productionOrder->reference}**.";
                break;
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting($greeting)
            ->line($line)
            ->action('Voir l\'Ordre de Fabrication', url('/production-orders/' . $this->productionOrder->id)) // Adapter l'URL
            ->line('Merci d\'utiliser notre application !');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'production_order_id' => $this->productionOrder->id,
            'reference' => $this->productionOrder->reference,
            'product_name' => $this->productionOrder->product->name,
            'quantity' => $this->productionOrder->quantity,
            'status' => $this->productionOrder->status->value,
            'type' => $this->type,
        ];
    }
}
