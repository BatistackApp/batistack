<?php

namespace App\Filament\Resources\Core\Plans\Schemas;

use App\Enums\Core\TypeFeature;
use App\Models\Core\Feature;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations Générales')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom du Plan')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('description')
                            ->label('Description')
                            ->maxLength(65535),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('price_monthly')
                                    ->label('Prix Mensuel (€)')
                                    ->numeric()
                                    ->prefix('€'),

                                TextInput::make('price_yearly')
                                    ->label('Prix Annuel (€)')
                                    ->numeric()
                                    ->prefix('€'),
                            ]),

                        Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),

                        Toggle::make('is_public')
                            ->label('Public')
                            ->default(true),
                    ]),

                Section::make('Fonctionnalités et Limites')
                    ->schema([
                        Repeater::make('features')
                            ->relationship('features')
                            ->schema([
                                Select::make('feature_id')
                                    ->label('Fonctionnalité')
                                    ->options(Feature::all()->pluck('name', 'id'))
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('type', Feature::find($state)?->type)),

                                TextInput::make('value')
                                    ->label('Valeur / Limite')
                                    ->helperText("Pour les quotas, indiquez le nombre max. Pour les modules, laissez vide ou mettez 'true'.")
                                    ->visible(fn (callable $get) => $get('type') === TypeFeature::LIMIT->value || $get('type') === 'limit'),

                                // Champ caché pour stocker le type et gérer l'affichage conditionnel
                                TextInput::make('type')
                                    ->hidden()
                                    ->dehydrated(false),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Ajouter une fonctionnalité'),
                    ]),
            ]);
    }
}
