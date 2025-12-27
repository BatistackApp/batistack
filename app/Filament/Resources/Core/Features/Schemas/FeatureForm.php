<?php

namespace App\Filament\Resources\Core\Features\Schemas;

use App\Enums\Core\TypeFeature;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class FeatureForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label("Code")
                    ->required(),

                TextInput::make('name')
                    ->label("Désignation")
                    ->required(),

                Select::make('type')
                    ->label("Type de Fonction")
                    ->options(TypeFeature::class),

                Toggle::make('is_optional')
                    ->label("Est en option"),

                MarkdownEditor::make('description')
                    ->label("Description"),
            ]);
    }
}
