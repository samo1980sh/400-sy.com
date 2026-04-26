<?php

namespace App\Filament\Resources\WarehouseInventoryItems;

use App\Filament\Resources\WarehouseInventoryItems\Pages\ListWarehouseInventoryItems;
use App\Filament\Resources\WarehouseInventoryItems\Tables\WarehouseInventoryItemsTable;
use App\Models\WarehouseInventoryItem;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class WarehouseInventoryItemResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = WarehouseInventoryItem::class;
    protected static ?string $permissionPrefix = 'warehouse-inventory';
    protected static ?string $modelLabel = 'مخزون';
    protected static ?string $pluralModelLabel = 'المخزون';
    protected static string|UnitEnum|null $navigationGroup = 'المستودعات';
    protected static ?string $navigationLabel = 'المخزون';
    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return WarehouseInventoryItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouseInventoryItems::route('/'),
        ];
    }
}
