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
    case Overtime25 = 'overtime_25'; // Heures supplémentaires 25%
    case Overtime50 = 'overtime_50'; // Heures supplémentaires 50%
    case NightHour = 'night_hour';   // Heures de nuit
    case SundayHour = 'sunday_hour'; // Heures du dimanche

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Work => 'Travail',
            self::Travel => 'Trajet',
            self::Absence => 'Absence',
            self::Training => 'Formation',
            self::Overtime25 => 'Heures Sup 25%',
            self::Overtime50 => 'Heures Sup 50%',
            self::NightHour => 'Heures de Nuit',
            self::SundayHour => 'Heures Dimanche',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Work => 'primary',
            self::Travel => 'warning',
            self::Absence => 'danger',
            self::Training => 'info',
            self::Overtime25, self::Overtime50 => 'success', // Heures sup en vert
            self::NightHour => 'secondary', // Heures de nuit en gris foncé
            self::SundayHour => 'warning', // Heures dimanche en orange
        };
    }
}
