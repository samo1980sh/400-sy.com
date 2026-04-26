<?php

namespace App\Filament\Resources\CompanyNewsTickerItems\Schemas;

use App\Models\CompanyNewsTickerItem;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CompanyNewsTickerItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(): array
    {
        return [
            TextInput::make('text_ar')
                ->label('النص بالعربية')
                ->extraInputAttributes(['dir' => 'rtl'])
                ->required()
                ->columnSpanFull(),
            TextInput::make('text_en')
                ->label('Text in English')
                ->extraInputAttributes(['dir' => 'ltr'])
                ->columnSpanFull(),
            TextInput::make('sort_order')
                ->label('الترتيب')
                ->numeric()
                ->default(fn (): int => (int) (CompanyNewsTickerItem::query()->max('sort_order') ?? 0) + 1),
            Toggle::make('status')
                ->label('فعال')
                ->default(true)
                ->inline(false)
                ->dehydrateStateUsing(fn (bool $state): string => $state ? 'active' : 'inactive')
                ->afterStateHydrated(function (Toggle $component, mixed $state): void {
                    $component->state(in_array($state, ['active', '1', 1, true], true));
                }),
        ];
    }
}
