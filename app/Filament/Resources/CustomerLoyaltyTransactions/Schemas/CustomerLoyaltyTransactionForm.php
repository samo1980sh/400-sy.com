<?php

namespace App\Filament\Resources\CustomerLoyaltyTransactions\Schemas;

use App\Models\Customer;
use App\Models\CustomerLoyaltyWallet;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CustomerLoyaltyTransactionForm
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
                    Select::make('customer_loyalty_wallet_id')
                        ->label('المحفظة')
                        ->options(fn (): array => CustomerLoyaltyWallet::query()
                            ->with('customer')
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn (CustomerLoyaltyWallet $wallet): array => [
                                $wallet->id => trim(($wallet->customer?->name ?? '-') . ' - ' . ($wallet->points_balance ?? 0)),
                            ])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('type')
                        ->label('نوع الحركة')
                        ->options([
                            'earn' => 'كسب',
                            'spend' => 'صرف',
                            'deduct' => 'خصم',
                            'adjust' => 'تعديل',
                            'expire' => 'انتهاء',
                            'hold' => 'حجز',
                        ])
                        ->default('earn')
                        ->required(),
                    TextInput::make('points')
                        ->label('عدد النقاط')
                        ->numeric()
                        ->required(),
                    TextInput::make('balance_before')
                        ->label('الرصيد قبل الحركة')
                        ->numeric()
                        ->required()
                        ->default(0),
                    TextInput::make('balance_after')
                        ->label('الرصيد بعد الحركة')
                        ->numeric()
                        ->required()
                        ->default(0),
                    TextInput::make('source_type')
                        ->label('مصدر الحركة')
                        ->maxLength(255),
                    TextInput::make('source_id')
                        ->label('رقم المصدر')
                        ->numeric(),
                    TextInput::make('reference_no')
                        ->label('المرجع')
                        ->maxLength(255),
                    DateTimePicker::make('occurred_at')
                        ->label('تاريخ الحركة')
                        ->default(now()),
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->columnSpanFull(),
                ]),
        ];
    }
}
