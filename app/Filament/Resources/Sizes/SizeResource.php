<?php

namespace App\Filament\Resources\Sizes;

use App\Filament\Resources\Sizes\Pages\CreateSize;
use App\Filament\Resources\Sizes\Pages\EditSize;
use App\Filament\Resources\Sizes\Pages\ListSizes;
use App\Filament\Resources\Sizes\Schemas\SizeForm;
use App\Filament\Resources\Sizes\Tables\SizesTable;
use App\Models\Size;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SizeResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = Size::class;
    protected static ?string $permissionPrefix = 'sizes';
    protected static ?string $modelLabel = 'قياس';
    protected static ?string $pluralModelLabel = 'القياسات';
    protected static string|UnitEnum|null $navigationGroup = 'المنتجات والمتجر';
    protected static ?string $navigationLabel = 'القياسات';
    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public static function form(Schema $schema): Schema
    {
        return SizeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SizesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSizes::route('/'),
            'create' => CreateSize::route('/create'),
            'edit' => EditSize::route('/{record}/edit'),
        ];
    }
}
