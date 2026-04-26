<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\PaymentMethod;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class OrderForm
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
                    Select::make('customer_id')
                        ->label('الزبون')
                        ->options(fn (): array => Customer::query()
                            ->orderBy('name')
                            ->get(['id', 'name', 'mobile', 'account_no'])
                            ->mapWithKeys(fn (Customer $customer): array => [
                                $customer->id => trim(implode(' - ', array_filter([
                                    $customer->name,
                                    $customer->mobile,
                                    $customer->account_no,
                                ]))),
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Set $set) => $set('shipping_address_id', null))
                        ->required(),
                    Select::make('shipping_address_id')
                        ->label('عنوان الشحن')
                        ->options(function (Get $get): array {
                            if (! filled($get('customer_id'))) {
                                return [];
                            }

                            return CustomerAddress::query()
                                ->where('customer_id', $get('customer_id'))
                                ->orderByDesc('is_default')
                                ->orderBy('label')
                                ->get(['id', 'label', 'city', 'area'])
                                ->mapWithKeys(fn (CustomerAddress $address): array => [
                                    $address->id => trim(implode(' - ', array_filter([
                                        $address->label,
                                        $address->city,
                                        $address->area,
                                    ]))),
                                ])
                                ->all();
                        })
                        ->searchable()
                        ->preload(),
                    Select::make('shipping_method_id')
                        ->label('طريقة الشحن')
                        ->options(fn (): array => ShippingMethod::query()
                            ->where('active', true)
                            ->orderBy('name_ar')
                            ->get(['id', 'name_ar', 'cost'])
                            ->mapWithKeys(fn (ShippingMethod $method): array => [
                                $method->id => trim(implode(' - ', array_filter([
                                    $method->name_ar,
                                    $method->cost !== null ? number_format((float) $method->cost, 2, '.', ',') : null,
                                ]))),
                            ])
                            ->all())
                        ->searchable()
                        ->preload(),
                    Select::make('status')
                        ->label('حالة الطلب')
                        ->options([
                            'pending' => 'قيد المراجعة',
                            'confirmed' => 'مؤكد',
                            'shipped' => 'مُشحن',
                            'delivered' => 'مُسلم',
                            'cancelled' => 'ملغى',
                        ])
                        ->default('pending')
                        ->required(),
                    Select::make('payment_status')
                        ->label('حالة الدفع')
                        ->options([
                            'unpaid' => 'غير مدفوع',
                            'paid' => 'مدفوع',
                        ])
                        ->default('unpaid')
                        ->required(),
                    Select::make('payment_method')
                        ->label('طريقة الدفع')
                        ->options(fn (): array => PaymentMethod::query()
                            ->where('active', true)
                            ->orderBy('name_ar')
                            ->pluck('name_ar', 'code')
                            ->all())
                        ->searchable()
                        ->preload(),
                    TextInput::make('branch')
                        ->label('الفرع')
                        ->maxLength(255),
                    TextInput::make('total_before_discount')
                        ->label('الإجمالي قبل الحسم')
                        ->numeric()
                        ->default(0),
                    TextInput::make('discount_value')
                        ->label('قيمة الحسم')
                        ->numeric()
                        ->default(0),
                    TextInput::make('shipping_cost')
                        ->label('أجرة الشحن')
                        ->numeric()
                        ->default(0),
                    Toggle::make('is_gift')
                        ->label('طلب هدية')
                        ->default(false),
                    Textarea::make('gift_message')
                        ->label('رسالة الهدية')
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->columnSpanFull(),
                    Repeater::make('items')
                        ->label('عناصر الطلب')
                        ->columns(2)
                        ->minItems(1)
                        ->itemNumbers()
                        ->schema([
                            Select::make('product_variant_id')
                                ->label('المتغير')
                                ->searchable()
                                ->getSearchResultsUsing(fn (string $search): array => ProductVariant::query()
                                    ->with(['product', 'productColor', 'size'])
                                    ->where(function ($query) use ($search): void {
                                        $query->whereHas('product', function ($productQuery) use ($search): void {
                                            $productQuery->where('title_ar', 'like', '%' . $search . '%')
                                                ->orWhere('model_no', 'like', '%' . $search . '%');
                                        })
                                        ->orWhere('sku', 'like', '%' . $search . '%')
                                        ->orWhere('barcode', 'like', '%' . $search . '%')
                                        ->orWhereHas('productColor', function ($colorQuery) use ($search): void {
                                            $colorQuery->where('color_name_ar', 'like', '%' . $search . '%')
                                                ->orWhere('color_code', 'like', '%' . $search . '%');
                                        })
                                        ->orWhereHas('size', function ($sizeQuery) use ($search): void {
                                            $sizeQuery->where('code', 'like', '%' . $search . '%')
                                                ->orWhere('name_ar', 'like', '%' . $search . '%');
                                        });
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(fn (ProductVariant $variant): array => [
                                        $variant->id => static::variantLabel($variant),
                                    ])
                                    ->all())
                                ->getOptionLabelUsing(fn ($value): ?string => static::variantLabel(
                                    ProductVariant::query()
                                        ->with(['product', 'productColor', 'size'])
                                        ->find($value)
                                ))
                                ->required(),
                            TextInput::make('quantity')
                                ->label('الكمية')
                                ->numeric()
                                ->default(1)
                                ->required(),
                            TextInput::make('unit_price')
                                ->label('سعر القطعة')
                                ->numeric()
                                ->default(0)
                                ->required(),
                        ])
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected static function variantLabel(?ProductVariant $variant): ?string
    {
        if (! $variant) {
            return null;
        }

        return trim(implode(' - ', array_filter([
            $variant->product?->model_no,
            $variant->product?->title_ar,
            $variant->productColor?->color_name_ar,
            $variant->size?->code,
        ])));
    }
}
