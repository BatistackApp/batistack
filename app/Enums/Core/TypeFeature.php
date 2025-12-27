<?php

namespace App\Enums\Core;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum TypeFeature: string implements HasLabel
{
    case MODULE = "module";
    case OPTION = "option";
    case SERVICE = "service";

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::MODULE => "Module",
            self::OPTION => "Option",
            self::SERVICE => "Service",
        };
    }
}
