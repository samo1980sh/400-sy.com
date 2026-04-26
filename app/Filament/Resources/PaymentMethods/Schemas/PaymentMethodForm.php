<?php

namespace App\Filament\Resources\PaymentMethods\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class PaymentMethodForm
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
                        ->label('الاسم بالعربي')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('name_en')
                        ->label('الاسم بالانكليزي')
                        ->maxLength(255),
                    TextInput::make('code')
                        ->label('الرمز')
                        ->required()
                        ->maxLength(255),
                    Toggle::make('active')
                        ->label('فعال')
                        ->default(true),
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->columnSpanFull(),
                ]),
        ];
    }
}
