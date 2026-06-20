<?php

namespace App\Filament\Resources\Colors;

use App\Filament\Resources\Colors\Pages\CreateColor;
use App\Filament\Resources\Colors\Pages\EditColor;
use App\Filament\Resources\Colors\Pages\ListColors;
use App\Filament\Resources\Colors\Schemas\ColorForm;
use App\Filament\Resources\Colors\Tables\ColorsTable;
use App\Models\Color;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ColorResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = Color::class;
    protected static ?string $permissionPrefix = 'colors';
    protected static ?string $modelLabel = 'لون';
    protected static ?string $pluralModelLabel = 'الألوان';
    protected static string|UnitEnum|null $navigationGroup = 'المنتجات والمتجر';
    protected static ?string $navigationLabel = 'الألوان';
    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    public static function form(Schema $schema): Schema
    {
        return ColorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ColorsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListColors::route('/'),
            'create' => CreateColor::route('/create'),
            'edit' => EditColor::route('/{record}/edit'),
        ];
    }
}
