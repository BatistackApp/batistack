<?php

namespace App\Filament\Resources\Core\Plans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Désignation')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('price_monthly')
                    ->label("Tarif Mensuel")
                    ->money('EUR'),

                TextColumn::make('price_yearly')
                    ->label("Tarif Annuel")
                    ->money('EUR'),

                IconColumn::make('is_active')
                    ->label("Actif"),

                IconColumn::make('is_public')
                    ->label("Afficher sur le site"),

                TextColumn::make('feature_count')
                    ->label("Nombre de fonctionnalités")
                    ->badge()
                    ->formatStateUsing(function (?Model $record) {
                        return $record->features->count();
                    }),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
