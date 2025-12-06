<?php

namespace App\Enums\Facturation;

use Filament\Support\Contracts\HasLabel;

enum SalesDocumentLineType: string implements HasLabel
{
    case Section = 'section';   // Titre de chapitre (ex: "Maçonnerie")
    case Product = 'product';   // Ligne standard
    case Comment = 'comment';   // Ligne de texte libre
    case Subtotal = 'subtotal'; // Sous-total technique (optionnel)

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Section => 'Titre / Section',
            self::Product => 'Article',
            self::Comment => 'Commentaire',
            self::Subtotal => 'Sous-total',
        };
    }
}
