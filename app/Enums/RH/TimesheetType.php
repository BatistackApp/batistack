<?php

namespace App\Enums\RH;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TimesheetType: string implements HasLabel, HasColor
{
    case Work = 'work';         // Travail effectif
    case Travel = 'travel';     // Trajet (parfois payé différemment)
    case Absence = 'absence';   // Congés / Maladie
    case Training = 'training'; // Formation

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Work => 'Travail',
            self::Travel => 'Trajet',
            self::Absence => 'Absence',
            self::Training => 'Formation',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Work => 'primary',
            self::Travel => 'warning',
            self::Absence => 'danger',
            self::Training => 'info',
        };
    }
}
