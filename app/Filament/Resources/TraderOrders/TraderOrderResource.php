<?php

namespace App\Filament\Resources\TraderOrders;

use App\Filament\Resources\TraderOrders\Pages\ListTraderOrders;
use App\Filament\Resources\TraderOrders\Tables\TraderOrdersTable;
use App\Models\TraderOrder;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class TraderOrderResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = TraderOrder::class;
    protected static ?string $permissionPrefix = 'trader-orders';
    protected static ?string $modelLabel = 'طلب تاجر';
    protected static ?string $pluralModelLabel = 'طلبات التجار';
    protected static string|UnitEnum|null $navigationGroup = 'تجار الجملة';
    protected static ?string $navigationLabel = 'طلبات التجار';
    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function table(Table $table): Table
    {
        return TraderOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTraderOrders::route('/'),
        ];
    }
}
