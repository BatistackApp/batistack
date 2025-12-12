<?php

namespace App\Enums\Fleets;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FleetAssignmentStatus: string implements HasLabel, HasColor
{
    case Scheduled = 'scheduled';   // Planifiée
    case Active = 'active';         // En cours
    case Completed = 'completed';   // Terminée
    case Cancelled = 'cancelled';   // Annulée

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Scheduled => 'Planifiée',
            self::Active => 'Active',
            self::Completed => 'Terminée',
            self::Cancelled => 'Annulée',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Scheduled => 'info',
            self::Active => 'success',
            self::Completed => 'gray',
            self::Cancelled => 'danger',
        };
    }
}
