<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\RetailCustomerGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CustomerForm
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
                    TextInput::make('name')
                        ->label('الاسم الكامل')
                        ->required()
                        ->maxLength(255),
                    DatePicker::make('birth_date')
                        ->label('تاريخ الميلاد')
                        ->native(false),
                    TextInput::make('nationality')
                        ->label('الجنسية')
                        ->maxLength(255),
                    TextInput::make('mobile')
                        ->label('رقم الموبايل')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('secondary_mobile')
                        ->label('رقم موبايل آخر')
                        ->maxLength(255),
                    Select::make('gender')
                        ->label('الجنس')
                        ->options([
                            'male' => 'ذكر',
                            'female' => 'أنثى',
                        ]),
                    TextInput::make('city')
                        ->label('المدينة')
                        ->maxLength(255),
                    TextInput::make('area')
                        ->label('المنطقة')
                        ->maxLength(255),
                    TextInput::make('job_title')
                        ->label('المهنة')
                        ->maxLength(255),
                    Select::make('marital_status')
                        ->label('الحالة الاجتماعية')
                        ->options([
                            'single' => 'أعزب',
                            'married' => 'متزوج',
                            'divorced' => 'مطلق',
                            'widowed' => 'أرمل',
                        ]),
                    TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->email()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Select::make('retail_group_ids')
                        ->label('فئات المفرق')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn (): array => RetailCustomerGroup::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()),
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
