<?php

namespace App\Filament\Resources\Core\Features\Pages;

use App\Filament\Resources\Core\Features\FeatureResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFeature extends CreateRecord
{
    protected static string $resource = FeatureResource::class;
}
