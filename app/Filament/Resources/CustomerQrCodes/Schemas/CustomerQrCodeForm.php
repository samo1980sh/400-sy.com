<?php

namespace App\Filament\Resources\CustomerQrCodes\Schemas;

use App\Models\Customer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CustomerQrCodeForm
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
                    TextInput::make('token')
                        ->label('الرمز الداخلي القديم')
                        ->helperText('يُحتفظ به للتوافق مع رموز QR القديمة. رمز الزبون الحالي يعتمد رقم الحساب مباشرة.')
                        ->disabled()
                        ->dehydrated(false)
                        ->maxLength(255),
                    Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'active' => 'فعال',
                            'inactive' => 'معطل',
                        ])
                        ->default('active')
                        ->required(),
                    TextInput::make('scan_count')
                        ->label('عدد مرات الاستخدام')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->default(0),
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->columnSpanFull(),
                ]),
        ];
    }
}
