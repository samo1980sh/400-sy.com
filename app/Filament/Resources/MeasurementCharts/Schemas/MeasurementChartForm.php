<?php

namespace App\Filament\Resources\MeasurementCharts\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class MeasurementChartForm
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
                    TextInput::make('name')
                        ->label('الاسم')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('size_code')
                        ->label('القياس')
                        ->required()
                        ->maxLength(50),
                    TextInput::make('chest')
                        ->label('الصدر')
                        ->numeric()
                        ->default(null),
                    TextInput::make('shoulder')
                        ->label('الكتف')
                        ->numeric()
                        ->default(null),
                    TextInput::make('waist')
                        ->label('الوسط')
                        ->numeric()
                        ->default(null),
                    TextInput::make('length')
                        ->label('الطول')
                        ->numeric()
                        ->default(null),
                    TextInput::make('sleeve')
                        ->label('الكم')
                        ->numeric()
                        ->default(null),
                    TextInput::make('collar')
                        ->label('الياقة')
                        ->numeric()
                        ->default(null),
                    TextInput::make('inside_leg')
                        ->label('وسط الرجل')
                        ->numeric()
                        ->default(null),
                    TextInput::make('waistline')
                        ->label('الخاصرة')
                        ->numeric()
                        ->default(null),
                    TextInput::make('thigh_width')
                        ->label('عرض الفخذ')
                        ->numeric()
                        ->default(null),
                    TextInput::make('leg_width')
                        ->label('عرض الرجل')
                        ->numeric()
                        ->default(null),
                    TextInput::make('leg_length')
                        ->label('طول الرجل')
                        ->numeric()
                        ->default(null),
                ]),
        ];
    }
}
