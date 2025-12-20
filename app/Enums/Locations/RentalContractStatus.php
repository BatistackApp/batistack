<?php

namespace App\Enums\Locations;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;

enum RentalContractStatus: string implements HasLabel, HasColor
{
    case Draft = 'draft';
    case Active = 'active';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Active => 'Actif',
            self::Expired => 'Expiré',
            self::Cancelled => 'Annulé',
            self::Completed => 'Terminé',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Active => 'success',
            self::Expired => 'warning',
            self::Cancelled => 'danger',
            self::Completed => 'info',
        };
    }
}
