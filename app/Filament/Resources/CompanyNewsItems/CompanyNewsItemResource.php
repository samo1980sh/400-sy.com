<?php

namespace App\Filament\Resources\CompanyNewsItems;

use App\Filament\Resources\CompanyNewsItems\Pages\ListCompanyNewsItems;
use App\Filament\Resources\CompanyNewsItems\Schemas\CompanyNewsItemForm;
use App\Filament\Resources\CompanyNewsItems\Tables\CompanyNewsItemsTable;
use App\Models\CompanyNewsItem;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CompanyNewsItemResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = CompanyNewsItem::class;
    protected static ?string $permissionPrefix = 'company-news-items';
    protected static ?string $modelLabel = 'خبر / حدث';
    protected static ?string $pluralModelLabel = 'الأخبار والأحداث';
    protected static string|UnitEnum|null $navigationGroup = 'محتوى الموقع والواجهة';
    protected static ?string $navigationLabel = 'الأخبار والأحداث';
    protected static ?int $navigationSort = 2;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return CompanyNewsItemForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyNewsItemsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanyNewsItems::route('/'),
        ];
    }
}
