<?php

namespace App\Filament\Resources\ProductWholesaleAvailabilities;

use App\Filament\Resources\ProductWholesaleAvailabilities\Pages\ListProductWholesaleAvailabilities;
use App\Filament\Resources\ProductWholesaleAvailabilities\Schemas\ProductWholesaleAvailabilityForm;
use App\Filament\Resources\ProductWholesaleAvailabilities\Tables\ProductWholesaleAvailabilitiesTable;
use App\Models\ProductWholesaleAvailability;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductWholesaleAvailabilityResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = ProductWholesaleAvailability::class;
    protected static ?string $permissionPrefix = 'product-wholesale-availabilities';
    protected static ?string $modelLabel = 'توافر تاجر';
    protected static ?string $pluralModelLabel = 'توافر التاجر';
    protected static bool $shouldRegisterNavigation = true;
    protected static string|UnitEnum|null $navigationGroup = 'التجار والجملة';
    protected static ?string $navigationLabel = 'توافر التاجر';
    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    public static function form(Schema $schema): Schema
    {
        return ProductWholesaleAvailabilityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductWholesaleAvailabilitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductWholesaleAvailabilities::route('/'),
        ];
    }
}
