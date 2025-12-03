<?php

namespace App\Enums\Articles;

use Filament\Support\Contracts\HasLabel;

enum ProductUnit: string implements HasLabel
{
    case Piece = 'u';
    case Hour = 'h';
    case Day = 'j';
    case M2 = 'm2';
    case M3 = 'm3';
    case LinearMeter = 'ml';
    case Kg = 'kg';
    case Liter = 'l';
    case Package = 'fft'; // Forfait

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Piece => 'Unité',
            self::Hour => 'Heure',
            self::Day => 'Jour',
            self::M2 => 'm²',
            self::M3 => 'm³',
            self::LinearMeter => 'ml',
            self::Kg => 'kg',
            self::Liter => 'Litre',
            self::Package => 'Forfait',
        };
    }
}
