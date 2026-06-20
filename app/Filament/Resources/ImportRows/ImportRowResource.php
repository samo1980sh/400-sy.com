<?php

namespace App\Filament\Resources\ImportRows;

use App\Filament\Resources\ImportRows\Pages\CreateImportRow;
use App\Filament\Resources\ImportRows\Pages\EditImportRow;
use App\Filament\Resources\ImportRows\Pages\ListImportRows;
use App\Filament\Resources\ImportRows\Schemas\ImportRowForm;
use App\Filament\Resources\ImportRows\Tables\ImportRowsTable;
use App\Models\ImportRow;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ImportRowResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = ImportRow::class;
    protected static ?string $permissionPrefix = 'import-rows';
    protected static ?string $modelLabel = 'سطر استيراد';
    protected static ?string $pluralModelLabel = 'أسطر الاستيراد';
    protected static bool $shouldRegisterNavigation = false;
    protected static string|UnitEnum|null $navigationGroup = 'المنتجات والمتجر';
    protected static ?string $navigationLabel = 'أسطر الاستيراد';
    protected static ?int $navigationSort = 9;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function form(Schema $schema): Schema
    {
        return ImportRowForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImportRowsTable::configure($table);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportRows::route('/'),
            'create' => CreateImportRow::route('/create'),
            'edit' => EditImportRow::route('/{record}/edit'),
        ];
    }
}
