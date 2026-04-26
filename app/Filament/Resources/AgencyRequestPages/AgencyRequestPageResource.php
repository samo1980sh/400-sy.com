<?php

namespace App\Filament\Resources\AgencyRequestPages;

use App\Filament\Resources\AgencyRequestPages\Pages\ListAgencyRequestPages;
use App\Filament\Resources\AgencyRequestPages\Schemas\AgencyRequestPageForm;
use App\Filament\Resources\AgencyRequestPages\Tables\AgencyRequestPagesTable;
use App\Models\AgencyRequestPage;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AgencyRequestPageResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = AgencyRequestPage::class;
    protected static ?string $permissionPrefix = 'agency-request-pages';
    protected static ?string $modelLabel = 'طلب وكالة';
    protected static ?string $pluralModelLabel = 'طلب وكالة';
    protected static string|UnitEnum|null $navigationGroup = 'معلومات الاتصال';
    protected static ?string $navigationLabel = 'طلب وكالة';
    protected static ?int $navigationSort = 3;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return AgencyRequestPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AgencyRequestPagesTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return true;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgencyRequestPages::route('/'),
        ];
    }
}
