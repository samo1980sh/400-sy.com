<?php

namespace App\Filament\Resources\WholesaleCustomerGroups\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class WholesaleCustomerGroupForm
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
                    TextInput::make('name_ar')
                        ->label('الاسم بالعربية')
                        ->required()
                        ->maxLength(150),
                    TextInput::make('name_en')
                        ->label('الاسم بالانكليزية')
                        ->maxLength(150),
                    TextInput::make('code')
                        ->label('الرمز')
                        ->maxLength(50)
                        ->unique(ignoreRecord: true),
                    TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(0)
                        ->required(),
                    Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'active' => 'فعال',
                            'inactive' => 'غير فعال',
                        ])
                        ->default('active')
                        ->required(),
                ]),
        ];
    }
}
