<?php

namespace App\Filament\Resources\CompanyPages;

use App\Filament\Resources\CompanyPages\Pages\ListCompanyPages;
use App\Filament\Resources\CompanyPages\Schemas\CompanyPageForm;
use App\Filament\Resources\CompanyPages\Tables\CompanyPagesTable;
use App\Models\CompanyPage;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CompanyPageResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = CompanyPage::class;
    protected static ?string $permissionPrefix = 'company-pages';
    protected static ?string $modelLabel = 'صفحة';
    protected static ?string $pluralModelLabel = 'حول الشركة';
    protected static string|UnitEnum|null $navigationGroup = 'معلومات الشركة';
    protected static ?string $navigationLabel = 'حول الشركة';
    protected static ?int $navigationSort = 1;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return CompanyPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyPagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanyPages::route('/'),
        ];
    }
}
