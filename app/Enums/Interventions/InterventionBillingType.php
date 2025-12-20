<?php

namespace App\Enums\Interventions;

use Filament\Support\Contracts\HasLabel;

enum InterventionBillingType: string implements HasLabel
{
    case TimeAndMaterial = 'time_and_material';
    case FixedPrice = 'fixed_price';
    case NonBillable = 'non_billable';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::TimeAndMaterial => 'Régie (Temps passé + Matériel)',
            self::FixedPrice => 'Forfait',
            self::NonBillable => 'Non Facturable (Garantie/Interne)',
        };
    }
}
