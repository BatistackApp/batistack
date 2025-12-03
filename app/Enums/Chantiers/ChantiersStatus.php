<?php

namespace App\Enums\Chantiers;

use Filament\Support\Icons\Heroicon;

enum ChantiersStatus: string
{
    case DRAFT = "draft";
    case ONGOING = "ongoing";
    case COMPLETED = "completed";
    case CANCELLED = "cancelled";

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => "Brouillon",
            self::ONGOING => "En cours",
            self::COMPLETED => "Terminé",
            self::CANCELLED => "Annulé",
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'mono',
            self::ONGOING => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function icon(): Heroicon
    {
        return match ($this) {
            self::DRAFT => Heroicon::OutlinedPencil,
            self::ONGOING => Heroicon::ArrowPathRoundedSquare,
            self::COMPLETED => Heroicon::CheckCircle,
            self::CANCELLED => Heroicon::XCircle,
        };
    }
}
