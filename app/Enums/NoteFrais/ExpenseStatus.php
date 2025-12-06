<?php

namespace App\Enums\NoteFrais;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ExpenseStatus: string implements HasLabel, HasColor
{
    case Draft = 'draft';           // Brouillon
    case Submitted = 'submitted';   // Soumis à validation
    case Approved = 'approved';     // Validé par le manager
    case Rejected = 'rejected';     // Refusé
    case Paid = 'paid';             // Remboursé au salarié

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Submitted => 'En attente',
            self::Approved => 'Validé',
            self::Rejected => 'Refusé',
            self::Paid => 'Remboursé',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Submitted => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
            self::Paid => 'info',
        };
    }
}
