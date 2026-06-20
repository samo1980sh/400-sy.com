<?php

namespace App\Filament\Resources\Traders;

use App\Filament\Resources\Traders\Pages\ListTraders;
use App\Filament\Resources\Traders\Schemas\TraderForm;
use App\Filament\Resources\Traders\Tables\TradersTable;
use App\Models\Trader;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TraderResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = Trader::class;
    protected static ?string $permissionPrefix = 'traders';
    protected static ?string $modelLabel = 'تاجر';
    protected static ?string $pluralModelLabel = 'التجار';
    protected static string|UnitEnum|null $navigationGroup = 'التجار والجملة';
    protected static ?string $navigationLabel = 'التجار';
    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function form(Schema $schema): Schema
    {
        return TraderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TradersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTraders::route('/'),
        ];
    }
}
