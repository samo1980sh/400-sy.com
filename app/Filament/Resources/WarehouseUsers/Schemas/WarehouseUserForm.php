<?php

namespace App\Filament\Resources\WarehouseUsers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class WarehouseUserForm
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
                    TextInput::make('account_no')
                        ->label('رقم الحساب')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('username')
                        ->label('اسم المستخدم')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('name')
                        ->label('الاسم الكامل')
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
                    Select::make('country')
                        ->label('البلد')
                        ->options([
                            'سوريا' => 'سوريا',
                        ])
                        ->default('سوريا')
                        ->required(),
                    Select::make('account_type')
                        ->label('الصنف')
                        ->options([
                            'point_of_sale' => 'نقطة بيع',
                            'wholesale_customer' => 'زبون جملة',
                            'sales_manager' => 'مدير مبيعات',
                        ])
                        ->default('point_of_sale')
                        ->required(),
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
