<?php

namespace App\Enums\Comptabilite;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum JournalType: string implements HasLabel, HasColor
{
    case Purchase = 'purchase'; // Achats (AC)
    case Sale = 'sale';         // Ventes (VT)
    case Bank = 'bank';         // Banque (BQ)
    case Cash = 'cash';         // Caisse (CA)
    case General = 'general';   // Opérations Diverses (OD)
    case Report = 'report';     // À Nouveaux (AN)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Purchase => 'Achats',
            self::Sale => 'Ventes',
            self::Bank => 'Banque',
            self::Cash => 'Caisse',
            self::General => 'Opérations Diverses (OD)',
            self::Report => 'À Nouveaux',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Purchase => 'danger',   // Dépenses
            self::Sale => 'success',      // Revenus
            self::Bank, self::Cash => 'info',         // Trésorerie
            self::General => 'gray',
            self::Report => 'warning',
        };
    }
}
