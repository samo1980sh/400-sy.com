<?php

namespace App\Filament\Resources\CompanySocialLinks;

use App\Filament\Resources\CompanySocialLinks\Pages\ListCompanySocialLinks;
use App\Filament\Resources\CompanySocialLinks\Schemas\CompanySocialLinkForm;
use App\Filament\Resources\CompanySocialLinks\Tables\CompanySocialLinksTable;
use App\Models\CompanySocialLink;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CompanySocialLinkResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = CompanySocialLink::class;
    protected static ?string $permissionPrefix = 'company-social-links';
    protected static ?string $modelLabel = 'رابط اجتماعي';
    protected static ?string $pluralModelLabel = 'روابط التواصل الاجتماعي';
    protected static string|UnitEnum|null $navigationGroup = 'محتوى الموقع والواجهة';
    protected static ?string $navigationLabel = 'روابط التواصل الاجتماعي';
    protected static ?int $navigationSort = 5;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return CompanySocialLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CompanySocialLinksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanySocialLinks::route('/'),
        ];
    }
}
