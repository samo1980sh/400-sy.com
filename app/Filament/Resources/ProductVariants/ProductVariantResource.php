<?php

namespace App\Filament\Resources\ProductVariants;

use App\Filament\Resources\ProductVariants\Pages\ListProductVariants;
use App\Filament\Resources\ProductVariants\Schemas\ProductVariantForm;
use App\Filament\Resources\ProductVariants\Tables\ProductVariantsTable;
use App\Models\ProductVariant;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductVariantResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = ProductVariant::class;
    protected static ?string $permissionPrefix = 'product-variants';
    protected static ?string $modelLabel = 'توافر قياس';
    protected static ?string $pluralModelLabel = 'توافر القياسات';
    protected static bool $shouldRegisterNavigation = true;
    protected static string|UnitEnum|null $navigationGroup = 'المنتجات والمتجر';
    protected static ?string $navigationLabel = 'توافر القياسات';
    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    public static function form(Schema $schema): Schema
    {
        return ProductVariantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductVariantsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductVariants::route('/'),
        ];
    }
}
