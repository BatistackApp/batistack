<?php

namespace App\Enums\GPAO;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ProductionOrderStatus: string implements HasLabel, HasColor
{
    case Draft = 'draft';           // Brouillon
    case Planned = 'planned';       // Planifié
    case InProgress = 'in_progress';// En cours de production
    case Completed = 'completed';   // Terminé
    case Cancelled = 'cancelled';   // Annulé

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Planned => 'Planifié',
            self::InProgress => 'En cours',
            self::Completed => 'Terminé',
            self::Cancelled => 'Annulé',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Planned => 'info',
            self::InProgress => 'warning',
            self::Completed => 'success',
            self::Cancelled => 'danger',
        };
    }
}
