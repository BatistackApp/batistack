<?php

namespace App\Enums\Facturation;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PurchaseDocumentStatus: string implements HasLabel, HasColor
{
    case Draft = 'draft';           // Brouillon
    case Received = 'received';     // Reçu du fournisseur
    case Approved = 'approved';     // Approuvé pour paiement
    case Paid = 'paid';             // Payé
    case Partial = 'partial';       // Payé partiellement
    case Overdue = 'overdue';       // En retard de paiement
    case Cancelled = 'cancelled';   // Annulé

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Received => 'Reçu',
            self::Approved => 'Approuvé',
            self::Paid => 'Payé',
            self::Partial => 'Paiement partiel',
            self::Overdue => 'En retard',
            self::Cancelled => 'Annulé',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Received => 'info',
            self::Approved => 'success',
            self::Paid => 'success',
            self::Partial => 'warning',
            self::Overdue, self::Cancelled => 'danger',
        };
    }
}
