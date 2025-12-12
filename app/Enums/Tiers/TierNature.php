<?php

namespace App\Enums\Tiers;

enum TierNature: string
{
    case COMPANY = "company";
    case INDIVIDUAL = "individual";
    case Employee = "employee";

    public function label()
    {
        return match ($this) {
            self::COMPANY => "Entreprise",
            self::INDIVIDUAL => "Particulier",
            self::Employee => "Employée",
        };
    }
}
