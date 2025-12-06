<?php

namespace App\Enums\Facturation;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum SalesDocumentStatus: string implements HasLabel, HasColor
{
    case Draft = 'draft';       // Brouillon
    case Sent = 'sent';         // Envoyé au client
    case Accepted = 'accepted'; // Signé / Validé (Devis)
    case Refused = 'refused';   // Refusé (Devis)
    case Paid = 'paid';         // Payé (Facture)
    case Partial = 'partial';   // Payé partiellement
    case Overdue = 'overdue';   // En retard de paiement
    case Cancelled = 'cancelled'; // Annulé

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Sent => 'Envoyé',
            self::Accepted => 'Accepté / Signé',
            self::Refused => 'Refusé',
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
            self::Sent => 'info',
            self::Accepted, self::Paid => 'success',
            self::Refused, self::Overdue, self::Cancelled => 'danger',
            self::Partial => 'warning',
        };
    }
}
