<?php

namespace App\Enums\Articles;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum ProductType: string implements HasLabel
{
    case Material = 'material';     // Fourniture (Stockable)
    case Labor = 'labor';           // Main d'oeuvre (Heures)
    case Equipment = 'equipment';   // Engin (Location interne)
    case Subcontracting = 'subcontracting'; // Sous-traitance
    case Assembly = 'assembly';     // Ouvrage (Composé)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Material => 'Fourniture',
            self::Labor => 'Main d\'œuvre',
            self::Equipment => 'Matériel / Engin',
            self::Subcontracting => 'Sous-traitance',
            self::Assembly => 'Ouvrage',
        };
    }
}
