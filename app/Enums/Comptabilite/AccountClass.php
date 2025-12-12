<?php

namespace App\Enums\Comptabilite;

use Filament\Support\Contracts\HasLabel;

enum AccountClass: int implements HasLabel
{
    case Capitals = 1;      // Capitaux
    case FixedAssets = 2;   // Immobilisations
    case Stocks = 3;        // Stocks
    case ThirdParties = 4;  // Tiers (Fournisseurs, Clients, État)
    case Financial = 5;     // Financiers (Banque, Caisse)
    case Expenses = 6;      // Charges
    case Products = 7;      // Produits

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Capitals => 'CLASSE 1 - Capitaux',
            self::FixedAssets => 'CLASSE 2 - Immobilisations',
            self::Stocks => 'CLASSE 3 - Stocks',
            self::ThirdParties => 'CLASSE 4 - Tiers',
            self::Financial => 'CLASSE 5 - Financiers',
            self::Expenses => 'CLASSE 6 - Charges',
            self::Products => 'CLASSE 7 - Produits',
        };
    }
}
