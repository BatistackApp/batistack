<?php

namespace App\Filament\Resources\Core\Companies;

use App\Filament\Resources\Core\Companies\Pages\CreateCompany;
use App\Filament\Resources\Core\Companies\Pages\EditCompany;
use App\Filament\Resources\Core\Companies\Pages\ListCompanies;
use App\Filament\Resources\Core\Companies\Schemas\CompanyForm;
use App\Filament\Resources\Core\Companies\Tables\CompaniesTable;
use App\Models\Core\Company;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CompanyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompaniesTable::configure($table);
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
            'index' => ListCompanies::route('/'),
            'create' => CreateCompany::route('/create'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
