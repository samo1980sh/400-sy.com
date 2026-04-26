<?php

namespace App\Filament\Resources\ShippingMethods;

use App\Filament\Resources\ShippingMethods\Pages\ListShippingMethods;
use App\Filament\Resources\ShippingMethods\Schemas\ShippingMethodForm;
use App\Filament\Resources\ShippingMethods\Tables\ShippingMethodsTable;
use App\Models\ShippingMethod;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ShippingMethodResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = ShippingMethod::class;
    protected static ?string $permissionPrefix = 'shipping-methods';
    protected static ?string $modelLabel = 'طريقة شحن';
    protected static ?string $pluralModelLabel = 'طرق الشحن';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة الطلبات';
    protected static ?string $navigationLabel = 'طرق الشحن';
    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    public static function form(Schema $schema): Schema
    {
        return ShippingMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShippingMethodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShippingMethods::route('/'),
        ];
    }
}
