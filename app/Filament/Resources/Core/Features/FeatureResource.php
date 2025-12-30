<?php

namespace App\Filament\Resources\Core\Features;

use App\Filament\Resources\Core\Features\Pages\CreateFeature;
use App\Filament\Resources\Core\Features\Pages\EditFeature;
use App\Filament\Resources\Core\Features\Pages\ListFeatures;
use App\Filament\Resources\Core\Features\Schemas\FeatureForm;
use App\Filament\Resources\Core\Features\Tables\FeaturesTable;
use App\Models\Core\Feature;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FeatureResource extends Resource
{
    protected static ?string $model = Feature::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = "Fonctionnalités";

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return FeatureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeaturesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeatures::route('/'),
            'create' => CreateFeature::route('/create'),
            'edit' => EditFeature::route('/{record}/edit'),
        ];
    }
}
