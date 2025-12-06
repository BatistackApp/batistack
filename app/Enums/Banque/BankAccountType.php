<?php

namespace App\Enums\Banque;

use Filament\Support\Contracts\HasLabel;

enum BankAccountType: string implements HasLabel
{
    case Bank = 'bank';         // Compte courant classique
    case Cash = 'cash';         // Caisse physique (Espèces)
    case Card = 'card';         // Carte Affaire (ex: Mooncard, Qonto)
    case Savings = 'savings';   // Compte épargne

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Bank => 'Compte Bancaire',
            self::Cash => 'Caisse (Espèces)',
            self::Card => 'Carte Bancaire',
            self::Savings => 'Épargne / Placement',
        };
    }
}
