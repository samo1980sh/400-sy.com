<?php

namespace App\Filament\Resources\GiftCards\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class GiftCardForm
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
                        ->preload(),
                    TextInput::make('code')
                        ->label('الرمز')
                        ->maxLength(255),
                    TextInput::make('recipient_name')
                        ->label('اسم المستفيد')
                        ->maxLength(255),
                    TextInput::make('display_name')
                        ->label('الاسم الظاهر على البطاقة')
                        ->maxLength(255),
                    TextInput::make('sender_name')
                        ->label('اسم المرسل')
                        ->maxLength(255),
                    TextInput::make('amount')
                        ->label('القيمة')
                        ->numeric()
                        ->required()
                        ->default(0),
                    TextInput::make('balance')
                        ->label('الرصيد')
                        ->numeric()
                        ->required()
                        ->default(0),
                    Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'draft' => 'مسودة',
                            'active' => 'فعالة',
                            'used' => 'مستخدمة',
                            'expired' => 'منتهية',
                            'cancelled' => 'ملغاة',
                        ])
                        ->default('draft')
                        ->required(),
                    DateTimePicker::make('issued_at')
                        ->label('تاريخ الإصدار'),
                    DateTimePicker::make('expires_at')
                        ->label('تاريخ الانتهاء'),
                    Textarea::make('message')
                        ->label('الرسالة')
                        ->columnSpanFull(),
                    Textarea::make('usage_instructions')
                        ->label('طريقة الاستخدام')
                        ->columnSpanFull(),
                    Textarea::make('redemption_terms')
                        ->label('شروط الصرف')
                        ->columnSpanFull(),
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->columnSpanFull(),
                ]),
        ];
    }
}
