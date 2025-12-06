<?php

namespace App\Enums\NoteFrais;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ExpenseCategory: string implements HasLabel, HasIcon
{
    case Meal = 'meal';             // Repas / Restaurant
    case Hotel = 'hotel';           // Hôtel / Hébergement
    case Fuel = 'fuel';             // Carburant
    case Toll = 'toll';             // Péage / Parking
    case Material = 'material';     // Petit matériel / Consommable
    case Transport = 'transport';   // Train / Avion
    case Other = 'other';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Meal => 'Restauration',
            self::Hotel => 'Hébergement',
            self::Fuel => 'Carburant',
            self::Toll => 'Péage / Parking',
            self::Material => 'Petit Matériel',
            self::Transport => 'Transport',
            self::Other => 'Autre',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Meal => 'heroicon-o-cake',
            self::Hotel => 'heroicon-o-home',
            self::Fuel => 'heroicon-o-fire', // ou un bidon
            self::Material => 'heroicon-o-wrench',
            default => 'heroicon-o-banknotes',
        };
    }
}
