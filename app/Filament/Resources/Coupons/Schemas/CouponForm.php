<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class CouponForm
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
                    TextInput::make('code')
                        ->label('الكود')
                        ->required()
                        ->maxLength(255),
                    Select::make('discount_type')
                        ->label('نوع الخصم')
                        ->options([
                            'percent' => 'نسبة مئوية',
                            'fixed' => 'قيمة مالية ثابتة',
                        ])
                        ->default('percent')
                        ->required(),
                    TextInput::make('discount_value')
                        ->label('قيمة الخصم')
                        ->numeric()
                        ->required()
                        ->default(0),
                    TextInput::make('currency')
                        ->label('العملة')
                        ->maxLength(20)
                        ->visible(fn (Get $get): bool => $get('discount_type') === 'fixed'),
                    DateTimePicker::make('starts_at')
                        ->label('تاريخ البداية'),
                    DateTimePicker::make('ends_at')
                        ->label('تاريخ النهاية'),
                    TextInput::make('usage_limit_per_customer')
                        ->label('عدد الاستخدام لكل حساب')
                        ->numeric()
                        ->required()
                        ->default(1),
                    Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'active' => 'فعال',
                            'inactive' => 'غير فعال',
                        ])
                        ->default('active')
                        ->required(),
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->columnSpanFull(),
                ]),
        ];
    }
}
