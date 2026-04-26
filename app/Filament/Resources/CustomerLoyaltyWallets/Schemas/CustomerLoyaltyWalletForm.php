<?php

namespace App\Filament\Resources\CustomerLoyaltyWallets\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CustomerLoyaltyWalletForm
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
                    TextInput::make('points_balance')
                        ->label('رصيد النقاط')
                        ->numeric()
                        ->required()
                        ->default(0),
                    TextInput::make('points_earned_total')
                        ->label('إجمالي النقاط المكتسبة')
                        ->numeric()
                        ->required()
                        ->default(0),
                    TextInput::make('points_spent_total')
                        ->label('إجمالي النقاط المصروفة')
                        ->numeric()
                        ->required()
                        ->default(0),
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
