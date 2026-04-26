<?php

namespace App\Filament\Resources\CustomerAddresses\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CustomerAddressForm
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
                    TextInput::make('label')
                        ->label('اسم العنوان')
                        ->maxLength(255),
                    TextInput::make('contact_name')
                        ->label('اسم المستلم')
                        ->maxLength(255),
                    TextInput::make('mobile')
                        ->label('رقم الهاتف')
                        ->maxLength(255),
                    TextInput::make('city')
                        ->label('المدينة')
                        ->maxLength(255),
                    TextInput::make('area')
                        ->label('المنطقة')
                        ->maxLength(255),
                    Select::make('address_type')
                        ->label('نوع العنوان')
                        ->options([
                            'home' => 'منزل',
                            'work' => 'عمل',
                            'shipping' => 'شحن',
                            'other' => 'أخرى',
                        ])
                        ->default('home')
                        ->required(),
                    Select::make('is_default')
                        ->label('افتراضي')
                        ->options([
                            1 => 'نعم',
                            0 => 'لا',
                        ])
                        ->default(0)
                        ->required(),
                    Textarea::make('address_line')
                        ->label('العنوان التفصيلي')
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->columnSpanFull(),
                ]),
        ];
    }
}
