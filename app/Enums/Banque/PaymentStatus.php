<?php

namespace App\Enums\Banque;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasLabel, HasColor
{
    case Pending = 'pending';       // En attente (Enregistré mais non vérifié/rapproché)
    case Cleared = 'cleared';       // Rapproché (Correspond à une transaction bancaire - encaissé/décaissé)
    case Failed = 'failed';         // Échoué (Refus bancaire)
    case Canceled = 'canceled';     // Annulé (Par l'utilisateur avant exécution)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Cleared => 'Rapproché/Encaissé',
            self::Failed => 'Échoué',
            self::Canceled => 'Annulé',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Cleared => 'success',
            self::Failed => 'danger',
            self::Canceled => 'gray',
        };
    }

    /**
     * Helper pour savoir si le paiement est finalisé et comptabilisé
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::Cleared, self::Failed, self::Canceled]);
    }
}
