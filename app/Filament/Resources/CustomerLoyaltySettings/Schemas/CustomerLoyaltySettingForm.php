<?php

namespace App\Filament\Resources\CustomerLoyaltySettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CustomerLoyaltySettingForm
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
                    Toggle::make('enabled')
                        ->label('فعال')
                        ->default(true),
                    Select::make('award_on_status')
                        ->label('احتساب النقاط عند الحالة')
                        ->options([
                            'delivered' => 'مُسلم',
                            'paid' => 'مدفوع',
                            'confirmed' => 'مؤكد',
                        ])
                        ->default('delivered')
                        ->required(),
                    Select::make('points_base')
                        ->label('أساس احتساب النقاط')
                        ->options([
                            'net_total' => 'الصافي بعد الحسم',
                            'grand_total' => 'الإجمالي النهائي',
                        ])
                        ->default('net_total')
                        ->required(),
                    TextInput::make('points_per_currency')
                        ->label('نقاط لكل وحدة عملة')
                        ->numeric()
                        ->required()
                        ->default(0),
                    Textarea::make('notes')
                        ->label('ملاحظات')
                        ->columnSpanFull(),
                ]),
        ];
    }
}
