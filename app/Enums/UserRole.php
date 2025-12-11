<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum UserRole: string implements HasColor, HasLabel
{
    case ADMINISTRATEUR = 'admin';
    case CLIENT = 'client';
    case FOURNISSEUR = 'fournisseur';
    case SALARIE = 'salarie';
    case COMPTABILITE = 'comptabilite';


    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ADMINISTRATEUR => "danger",
            self::CLIENT => "success",
            self::FOURNISSEUR => "warning",
            self::SALARIE, self::COMPTABILITE => "info",
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::ADMINISTRATEUR => "Administrateur",
            self::CLIENT => "Client",
            self::FOURNISSEUR => "Fournisseur",
            self::SALARIE => "Salarié",
            self::COMPTABILITE => "Comptable",
        };
    }
}
