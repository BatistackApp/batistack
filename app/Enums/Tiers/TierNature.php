<?php

namespace App\Enums\Tiers;

enum TierNature: string
{
    case COMPANY = "company";
    case INDIVIDUAL = "individual";

    public function label()
    {
        return match ($this) {
            self::COMPANY => "Entreprise",
            self::INDIVIDUAL => "Particulier",
        };
    }
}
