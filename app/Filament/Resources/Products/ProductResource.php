<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\ComplementaryProductsRelationManager;
use App\Filament\Resources\Products\RelationManagers\ProductColorsRelationManager;
use App\Filament\Resources\Products\RelationManagers\ProductDetailsRelationManager;
use App\Filament\Resources\Products\RelationManagers\ProductImagesRelationManager;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Filament\Resources\Products\RelationManagers\WholesaleQuantitiesRelationManager;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProductResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = Product::class;
    protected static ?string $permissionPrefix = 'products';
    protected static ?string $modelLabel = 'منتج';
    protected static ?string $pluralModelLabel = 'المنتجات';
    protected static string|UnitEnum|null $navigationGroup = 'المنتجات والمتجر';
    protected static ?string $navigationLabel = 'المنتجات';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ProductImagesRelationManager::class,
            ProductDetailsRelationManager::class,
            ProductColorsRelationManager::class,
            VariantsRelationManager::class,
            WholesaleQuantitiesRelationManager::class,
            ComplementaryProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
