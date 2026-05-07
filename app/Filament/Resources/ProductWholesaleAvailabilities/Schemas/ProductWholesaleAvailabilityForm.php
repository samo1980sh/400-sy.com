<?php

namespace App\Filament\Resources\ProductWholesaleAvailabilities\Schemas;

use App\Models\Product;
use App\Models\ProductWholesaleColor;
use App\Models\WholesaleCustomerGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ProductWholesaleAvailabilityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(): array
    {
        return [
            Grid::make(2)->schema([
                Select::make('product_id')
                    ->label('رمز المنتج')
                    ->options(fn (): array => Product::query()
                        ->orderBy('model_no')
                        ->orderBy('title_ar')
                        ->get(['id', 'model_no', 'title_ar'])
                        ->mapWithKeys(fn (Product $product): array => [
                            $product->id => trim(implode(' — ', array_filter([
                                $product->model_no,
                                $product->title_ar,
                            ]))),
                        ])
                        ->all())
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('product_wholesale_color_id', null))
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('wholesale_customer_group_id')
                    ->label('فئة التاجر')
                    ->relationship('wholesaleCustomerGroup', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
            ]),
            Grid::make(2)->schema([
                Select::make('product_wholesale_color_id')
                    ->label('لون الجملة')
                    ->options(function (Get $get): array {
                        $productId = (int) ($get('product_id') ?? 0);

                        if ($productId <= 0) {
                            return [];
                        }

                        return ProductWholesaleColor::query()
                            ->where('product_id', $productId)
                            ->orderBy('id')
                            ->get()
                            ->mapWithKeys(function (ProductWholesaleColor $color): array {
                                $label = trim(implode(' — ', array_filter([
                                    $color->color_code,
                                    $color->color_name_ar,
                                ])));

                                return [$color->id => $label !== '' ? $label : '#'.$color->id];
                            })
                            ->all();
                    })
                    ->searchable()
                    ->required(),
                TextInput::make('max_quantity')
                    ->label('الكمية العظمى')
                    ->numeric()
                    ->required(),
            ]),
        ];
    }
}
