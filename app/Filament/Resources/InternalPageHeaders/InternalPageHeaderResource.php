<?php

namespace App\Filament\Resources\InternalPageHeaders;

use App\Filament\Resources\InternalPageHeaders\Pages\ListInternalPageHeaders;
use App\Filament\Resources\InternalPageHeaders\Schemas\InternalPageHeaderForm;
use App\Filament\Resources\InternalPageHeaders\Tables\InternalPageHeadersTable;
use App\Models\InternalPageHeader;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class InternalPageHeaderResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = InternalPageHeader::class;
    protected static ?string $permissionPrefix = 'internal-page-headers';
    protected static ?string $modelLabel = 'هيدر داخلي';
    protected static ?string $pluralModelLabel = 'هيدرات الصفحات الداخلية';
    protected static string|UnitEnum|null $navigationGroup = 'محتوى الموقع والواجهة';
    protected static ?string $navigationLabel = 'هيدرات الصفحات الداخلية';
    protected static ?int $navigationSort = 6;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return InternalPageHeaderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InternalPageHeadersTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
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
            'index' => ListInternalPageHeaders::route('/'),
        ];
    }
}
