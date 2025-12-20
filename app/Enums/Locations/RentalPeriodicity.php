<?php

namespace App\Enums\Locations;

use Filament\Support\Contracts\HasLabel;

enum RentalPeriodicity: string implements HasLabel
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Daily => 'Journalier',
            self::Weekly => 'Hebdomadaire',
            self::Monthly => 'Mensuel',
        };
    }
}
