<?php

namespace App\Filament\Resources\PointsVouchers\Schemas;

use App\Models\RetailCustomerGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class PointsVoucherForm
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
                        ->label('الرمز')
                        ->maxLength(255),
                    TextInput::make('name')
                        ->label('الاسم')
                        ->required()
                        ->maxLength(255),
                    Select::make('retail_customer_group_id')
                        ->label('فئة الزبون')
                        ->options(fn (): array => RetailCustomerGroup::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->preload(),
                    TextInput::make('points_required')
                        ->label('النقاط المطلوبة')
                        ->numeric()
                        ->required()
                        ->default(0),
                    TextInput::make('voucher_value')
                        ->label('قيمة القسيمة')
                        ->numeric()
                        ->required()
                        ->default(0),
                    TextInput::make('usage_method')
                        ->label('طريقة الاستخدام')
                        ->maxLength(255),
                    TextInput::make('branch')
                        ->label('الفرع')
                        ->maxLength(255),
                    TextInput::make('valid_days')
                        ->label('مدة الصلاحية باليوم')
                        ->numeric(),
                    Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'active' => 'فعالة',
                            'inactive' => 'غير فعالة',
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
