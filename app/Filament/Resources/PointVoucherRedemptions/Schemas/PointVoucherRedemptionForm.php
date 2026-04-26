<?php

namespace App\Filament\Resources\PointVoucherRedemptions\Schemas;

use App\Models\Customer;
use App\Models\PointsVoucher;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class PointVoucherRedemptionForm
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
                            ->get()
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
                        ->required(),
                    Select::make('points_voucher_id')
                        ->label('القسيمة')
                        ->options(fn (): array => PointsVoucher::query()
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(fn (PointsVoucher $voucher): array => [
                                $voucher->id => trim(implode(' - ', array_filter([
                                    $voucher->name,
                                    $voucher->code,
                                ]))),
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('order_no')
                        ->label('رقم الطلب')
                        ->maxLength(255),
                    TextInput::make('customer_name')
                        ->label('اسم المستخدم')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('account_no')
                        ->label('رقم الحساب')
                        ->maxLength(255),
                    TextInput::make('mobile')
                        ->label('رقم الموبايل')
                        ->maxLength(255),
                    TextInput::make('voucher_value')
                        ->label('قيمة القسيمة')
                        ->numeric()
                        ->required()
                        ->default(0),
                    TextInput::make('points_spent')
                        ->label('النقاط المصروفة')
                        ->numeric()
                        ->required()
                        ->default(0),
                    TextInput::make('usage_method')
                        ->label('طريقة الاستخدام')
                        ->maxLength(255),
                    TextInput::make('branch')
                        ->label('الفرع')
                        ->maxLength(255),
                    Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'pending' => 'معلقة',
                            'available' => 'متاحة',
                            'redeemed' => 'مصروفة',
                            'expired' => 'منتهية',
                            'cancelled' => 'ملغاة',
                        ])
                        ->default('pending')
                        ->required(),
                    DateTimePicker::make('issued_at')
                        ->label('تاريخ الإنشاء')
                        ->default(now()),
                    DateTimePicker::make('expires_at')
                        ->label('تاريخ الانتهاء'),
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->columnSpanFull(),
                ]),
        ];
    }
}
