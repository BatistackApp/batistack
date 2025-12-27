<?php

namespace App\Filament\Resources\Core\Features\Pages;

use App\Filament\Resources\Core\Features\FeatureResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeatures extends ListRecords
{
    protected static string $resource = FeatureResource::class;
    protected ?string $heading = "Liste des fonctionnalités";

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
