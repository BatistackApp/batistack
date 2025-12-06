<?php

namespace App\Enums\Facturation;

use Filament\Support\Contracts\HasLabel;

enum SalesDocumentType: string implements HasLabel
{
    case Quote = 'quote';           // Devis
    case Order = 'order';           // Commande (optionnel)
    case Invoice = 'invoice';       // Facture
    case CreditNote = 'credit_note'; // Avoir
    case Deposit = 'deposit';       // Facture d'acompte

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Quote => 'Devis',
            self::Order => 'Bon de Commande',
            self::Invoice => 'Facture',
            self::CreditNote => 'Avoir',
            self::Deposit => 'Acompte',
        };
    }
}
