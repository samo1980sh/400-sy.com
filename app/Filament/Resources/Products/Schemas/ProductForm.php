<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('بيانات المنتج')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('الأساسيات')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('category_id')
                                            ->label('التصنيف')
                                            ->options(fn (): array => Category::leafBreadcrumbOptions())
                                            ->native()
                                            ->required()
                                            ->rule(function () {
                                                return function (string $attribute, mixed $value, \Closure $fail): void {
                                                    if (! $value) {
                                                        return;
                                                    }

                                                    $category = Category::query()->find($value);

                                                    if (! $category || ! $category->isLeaf()) {
                                                        $fail('يجب اختيار تصنيف نهائي لا يحتوي على تصنيفات فرعية.');
                                                    }
                                                };
                                            }),
                                        TextInput::make('model_no')
                                            ->label('الكود')
                                            ->maxLength(50)
                                            ->required(),
                                        TextInput::make('title_ar')
                                            ->label('الاسم بالعربي')
                                            ->maxLength(255)
                                            ->required(),
                                        TextInput::make('title_en')
                                            ->label('الاسم بالإنكليزي')
                                            ->maxLength(255)
                                            ->required(),
                                        Textarea::make('description_ar')
                                            ->label('الوصف بالعربي')
                                            ->default(null),
                                        Textarea::make('description_en')
                                            ->label('الوصف بالإنكليزي')
                                            ->default(null),
                                        TextInput::make('price')
                                            ->label('السعر بعد الحسم')
                                            ->numeric()
                                            ->default(null),
                                        TextInput::make('compare_price')
                                            ->label('السعر قبل الحسم')
                                            ->numeric()
                                            ->default(null),
                                        TextInput::make('structure')
                                            ->label('لون المنتج المعروض / المواصفة')
                                            ->maxLength(100)
                                            ->default(null)
                                            ->helperText('هذا النص للعرض والمواصفات فقط، ويمكن أن يكون مركبًا مثل أسود ورمادي.'),
                                        TextInput::make('collection')
                                            ->label('التشكيلة')
                                            ->maxLength(100)
                                            ->default(null),
                                        TextInput::make('body_fit')
                                            ->label('Body Fit')
                                            ->maxLength(100)
                                            ->default(null),
                                        TextInput::make('drop_type')
                                            ->label('Drop')
                                            ->maxLength(100)
                                            ->default(null),
                                    ]),
                                Grid::make(2)
                                    ->columnSpanFull()
                                    ->schema([
                                        Grid::make(1)
                                            ->schema([
                                                Toggle::make('is_best_seller')
                                                    ->label('الأكثر مبيعًا')
                                                    ->default(false),
                                                Toggle::make('is_new')
                                                    ->label('جديد')
                                                    ->default(false),
                                                Toggle::make('is_special_offer')
                                                    ->label('عرض خاص')
                                                    ->default(false),
                                                Toggle::make('is_active')
                                                    ->label('فعّال')
                                                    ->default(true),
                                            ]),
                                        Grid::make(1)
                                            ->schema([
                                                Toggle::make('show_web')
                                                    ->label('يظهر على الموقع')
                                                    ->default(false),
                                                Toggle::make('show_app')
                                                    ->label('يظهر على التطبيق')
                                                    ->default(false),
                                                Toggle::make('show_retail')
                                                    ->label('يظهر للزبون')
                                                    ->helperText('فعّل هذا الخيار إذا كان المنتج متاحًا لعملاء المفرق.')
                                                    ->default(false),
                                                Toggle::make('show_wholesale')
                                                    ->label('يظهر للتاجر')
                                                    ->helperText('فعّل هذا الخيار إذا كان المنتج متاحًا لتجار الجملة.')
                                                    ->default(false),
                                            ]),
                                    ]),
                            ]),
                    ]),
            ]);
    }
}
