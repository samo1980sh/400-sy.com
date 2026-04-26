<?php

namespace App\Filament\Resources\CompanyHeaderImages;

use App\Filament\Resources\CompanyHeaderImages\Pages\ListCompanyHeaderImages;
use App\Filament\Resources\CompanyHeaderImages\Schemas\CompanyHeaderImageForm;
use App\Filament\Resources\CompanyHeaderImages\Tables\CompanyHeaderImagesTable;
use App\Models\CompanyHeaderImage;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CompanyHeaderImageResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = CompanyHeaderImage::class;
    protected static ?string $permissionPrefix = 'company-header-images';
    protected static ?string $modelLabel = 'سلايدر';
    protected static ?string $pluralModelLabel = 'سلايدر الصفحة الرئيسية';
    protected static string|UnitEnum|null $navigationGroup = 'معلومات الشركة';
    protected static ?string $navigationLabel = 'سلايدر الصفحة الرئيسية';
    protected static ?int $navigationSort = 3;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return CompanyHeaderImageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanyHeaderImagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanyHeaderImages::route('/'),
        ];
    }
}
