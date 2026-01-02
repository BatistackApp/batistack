<?php

namespace App\Enums\Core;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum TypeFeature: string implements HasLabel, HasColor
{
    case MODULE = "module";
    case OPTION = "option";
    case SERVICE = "service";
    case LIMIT = "limit";

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::MODULE => "Module",
            self::OPTION => "Option",
            self::SERVICE => "Service",
            self::LIMIT => "Limite / Quota",
        };
    }

    public function getColor(): string|array|null
    {
        // TODO: Implement getColor() method.
        return match ($this) {
            self::MODULE => "accent",
            self::OPTION => "warning",
            self::SERVICE => "info",
            self::LIMIT => "neutral",
        };
    }
}
