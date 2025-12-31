<?php

namespace App\Filament\Resources\Core\Subscriptions;

use App\Filament\Resources\Core\Subscriptions\Pages\CreateSubscription;
use App\Filament\Resources\Core\Subscriptions\Pages\EditSubscription;
use App\Filament\Resources\Core\Subscriptions\Pages\ListSubscriptions;
use App\Filament\Resources\Core\Subscriptions\Schemas\SubscriptionForm;
use App\Filament\Resources\Core\Subscriptions\Tables\SubscriptionsTable;
use App\Models\Core\Subscription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return SubscriptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionsTable::configure($table);
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
            'index' => ListSubscriptions::route('/'),
            'create' => CreateSubscription::route('/create'),
            'edit' => EditSubscription::route('/{record}/edit'),
        ];
    }
}
