<?php

namespace App\Enums\Banque;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case Transfer = 'transfer';     // Virement
    case Check = 'check';           // Chèque
    case Card = 'card';             // CB
    case Cash = 'cash';             // Espèces
    case DirectDebit = 'direct_debit'; // Prélèvement
    case BillOfExchange = 'lcr';    // LCR (Lettre de Change Relevé - fréquent en BTP)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Transfer => 'Virement',
            self::Check => 'Chèque',
            self::Card => 'Carte Bancaire',
            self::Cash => 'Espèces',
            self::DirectDebit => 'Prélèvement',
            self::BillOfExchange => 'LCR / Traite',
        };
    }
}
