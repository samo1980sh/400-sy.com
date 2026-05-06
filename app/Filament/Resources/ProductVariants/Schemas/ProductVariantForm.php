<?php

namespace App\Filament\Resources\ProductVariants\Schemas;

use App\Models\ProductColor;
use App\Models\Product;
use App\Models\Size;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProductVariantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(): array
    {
        return [
            Grid::make()
                ->columns(2)
                ->schema([
                    Select::make('product_id')
                        ->label('رمز المنتج')
                        ->options(fn (): array => Product::query()
                            ->orderBy('model_no')
                            ->orderBy('title_ar')
                            ->get()
                            ->mapWithKeys(fn (Product $product): array => [
                                $product->id => trim(($product->model_no ?: '-') . ' — ' . ($product->title_ar ?: '-')),
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('product_color_id')
                        ->label('اللون')
                        ->options(fn (Get $get): array => ProductColor::query()
                            ->when(filled($get('product_id')), fn ($query) => $query->where('product_id', $get('product_id')))
                            ->get()
                            ->sortBy(fn (ProductColor $productColor): string => (string) ($productColor->color_name_ar ?? ''))
                            ->mapWithKeys(fn (ProductColor $productColor): array => [
                                $productColor->id => trim(($productColor->color_name_ar ?: '-') . ' (' . ($productColor->color_code ?: '-') . ')'),
                            ])
                            ->all())
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('size_id')
                        ->label('القياس')
                        ->options(fn (): array => Size::query()
                            ->orderBy('sort_order')
                            ->get()
                            ->mapWithKeys(fn (Size $size): array => [
                                $size->id => trim($size->code . ' (' . ($size->name_ar ?: $size->code) . ')'),
                            ])
                            ->all())
                        ->searchable()
                        ->preload(),
                    TextInput::make('price')
                        ->label('بيع')
                        ->required()
                        ->numeric()
                        ->inputMode('decimal')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->default(0.0)
                        ->prefix(fn (Get $get): string => Product::find($get('product_id'))?->currency_ar ?? 'SYP'),
                    TextInput::make('compare_price')
                        ->label('كرت')
                        ->numeric()
                        ->inputMode('decimal')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->default(null)
                        ->prefix(fn (Get $get): string => Product::find($get('product_id'))?->currency_ar ?? 'SYP'),
                    TextInput::make('quantity')
                        ->label('الكمية')
                        ->required()
                        ->numeric()
                        ->inputMode('decimal')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->default(0),
                    Select::make('status')
                        ->label('الحالة')
                        ->options(['active' => 'فعال', 'inactive' => 'غير فعال'])
                        ->default('active')
                        ->required(),
                ]),
        ];
    }
}
