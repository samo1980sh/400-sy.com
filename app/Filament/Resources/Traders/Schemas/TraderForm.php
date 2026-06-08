<?php

namespace App\Filament\Resources\Traders\Schemas;

use App\Models\WholesaleCustomerGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class TraderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(): array
    {
        return [
            Grid::make()
                ->columnSpanFull()
                ->columns(['default' => 1, 'md' => 2])
                ->schema([
                    TextInput::make('account_no')
                        ->label('رقم الحساب')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('name')
                        ->label('اسم التاجر')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('mobile')
                        ->label('رقم الموبايل')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('secondary_mobile')
                        ->label('رقم موبايل آخر')
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Select::make('wholesale_customer_group_id')
                        ->label('فئة التاجر')
                        ->relationship('wholesaleCustomerGroup', 'name_ar')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('city')
                        ->label('المدينة')
                        ->maxLength(255),
                    TextInput::make('area')
                        ->label('المنطقة')
                        ->maxLength(255),
                    TextInput::make('address_line')
                        ->label('العنوان')
                        ->columnSpanFull(),
                    TextInput::make('password')
                        ->label('كلمة المرور')
                        ->password()
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->dehydrated(fn ($state): bool => filled($state))
                        ->minLength(8),
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
