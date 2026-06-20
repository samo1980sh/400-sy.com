<?php

namespace App\Filament\Resources\CompanyNewsTickerItems;

use App\Filament\Resources\CompanyNewsTickerItems\Pages\ListCompanyNewsTickerItems;
use App\Filament\Resources\CompanyNewsTickerItems\Schemas\CompanyNewsTickerItemForm;
use App\Filament\Resources\CompanyNewsTickerItems\Tables\CompanyNewsTickerItemsTable;
use App\Models\CompanyNewsTickerItem;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CompanyNewsTickerItemResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = CompanyNewsTickerItem::class;
    protected static ?string $permissionPrefix = 'company-news-ticker-items';
    protected static ?string $modelLabel = 'شريط إخباري';
    protected static ?string $pluralModelLabel = 'الشريط الإخباري المتحرك';
    protected static string|UnitEnum|null $navigationGroup = 'محتوى الموقع والواجهة';
    protected static ?string $navigationLabel = 'الشريط الإخباري المتحرك';
    protected static ?int $navigationSort = 4;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return CompanyNewsTickerItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyNewsTickerItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanyNewsTickerItems::route('/'),
        ];
    }
}
