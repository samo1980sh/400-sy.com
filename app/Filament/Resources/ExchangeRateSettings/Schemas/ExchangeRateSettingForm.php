<?php

namespace App\Filament\Resources\ExchangeRateSettings\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ExchangeRateSettingForm
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
                    TextInput::make('base_currency_code')
                        ->label('العملة الأساسية')
                        ->default('SYP')
                        ->disabled()
                        ->dehydrated()
                        ->maxLength(10),
                    Toggle::make('show_usd')
                        ->label('إظهار بالدولار')
                        ->default(true),
                    TextInput::make('usd_syp_rate')
                        ->label('سعر 1 دولار بالليرة السورية')
                        ->numeric()
                        ->inputMode('decimal')
                        ->step('0.0001')
                        ->placeholder('130.25')
                        ->required()
                        ->default(0)
                        ->minValue(0),
                    Toggle::make('show_eur')
                        ->label('إظهار باليورو')
                        ->default(true),
                    TextInput::make('eur_syp_rate')
                        ->label('سعر 1 يورو بالليرة السورية')
                        ->numeric()
                        ->inputMode('decimal')
                        ->step('0.0001')
                        ->placeholder('130.25')
                        ->required()
                        ->default(0)
                        ->minValue(0),
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->columnSpanFull(),
                ]),
        ];
    }
}
