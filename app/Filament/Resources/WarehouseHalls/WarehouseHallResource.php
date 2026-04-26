<?php

namespace App\Filament\Resources\WarehouseHalls;

use App\Filament\Resources\WarehouseHalls\Pages\ListWarehouseHalls;
use App\Filament\Resources\WarehouseHalls\Schemas\WarehouseHallForm;
use App\Filament\Resources\WarehouseHalls\Tables\WarehouseHallsTable;
use App\Models\WarehouseHall;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WarehouseHallResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = WarehouseHall::class;
    protected static ?string $permissionPrefix = 'warehouse-halls';
    protected static ?string $modelLabel = 'صالة';
    protected static ?string $pluralModelLabel = 'الصالات';
    protected static string|UnitEnum|null $navigationGroup = 'المستودعات';
    protected static ?string $navigationLabel = 'الصالات';
    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public static function form(Schema $schema): Schema
    {
        return WarehouseHallForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehouseHallsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouseHalls::route('/'),
        ];
    }
}
