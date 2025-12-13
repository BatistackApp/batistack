<?php

namespace App\Enums\Locations;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum RentalContractStatus: string implements HasLabel, HasColor
{
    case Draft = 'draft';           // Brouillon
    case Active = 'active';         // Actif
    case Completed = 'completed';   // Terminé
    case Cancelled = 'cancelled';   // Annulé
    case Overdue = 'overdue';       // En retard (ex: non retourné à temps)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Active => 'Actif',
            self::Completed => 'Terminé',
            self::Cancelled => 'Annulé',
            self::Overdue => 'En retard',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Active => 'success',
            self::Completed => 'primary',
            self::Cancelled => 'danger',
            self::Overdue => 'warning',
        };
    }
}
