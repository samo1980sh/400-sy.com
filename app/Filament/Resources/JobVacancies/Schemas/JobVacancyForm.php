<?php

namespace App\Filament\Resources\JobVacancies\Schemas;

use App\Models\JobVacancy;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class JobVacancyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(?JobVacancy $editingRecord = null): array
    {
        return [
            Grid::make()
                ->columns(4)
                ->schema([
                    TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(fn (): int => ((int) (JobVacancy::max('sort_order') ?? 0)) + 1)
                        ->required()
                        ->columnSpan(1),
                    Grid::make()
                        ->columns(2)
                        ->schema([
                            TextInput::make('title_ar')
                                ->label('العنوان بالعربية')
                                ->extraInputAttributes(['dir' => 'rtl'])
                                ->required()
                                ->maxLength(255),
                            TextInput::make('title_en')
                                ->label('العنوان بالانكليزية')
                                ->extraInputAttributes(['dir' => 'ltr'])
                                ->required()
                                ->maxLength(255),
                        ])
                        ->columnSpanFull(),
                    Grid::make()
                        ->columns(2)
                        ->schema([
                            TextInput::make('location_ar')
                                ->label('الموقع بالعربية')
                                ->extraInputAttributes(['dir' => 'rtl'])
                                ->maxLength(255),
                            TextInput::make('location_en')
                                ->label('الموقع بالانكليزية')
                                ->extraInputAttributes(['dir' => 'ltr'])
                                ->maxLength(255),
                        ])
                        ->columnSpanFull(),
                    DatePicker::make('deadline_at')
                        ->label('آخر موعد')
                        ->columnSpanFull(),
                    RichEditor::make('description_ar')
                        ->label('الوصف بالعربية')
                        ->extraInputAttributes(['dir' => 'rtl', 'style' => 'min-height: 16rem;'])
                        ->columnSpanFull(),
                    RichEditor::make('description_en')
                        ->label('الوصف بالانكليزية')
                        ->extraInputAttributes(['dir' => 'ltr', 'style' => 'min-height: 16rem;'])
                        ->columnSpanFull(),
                    RichEditor::make('requirements_ar')
                        ->label('الشروط بالعربية')
                        ->extraInputAttributes(['dir' => 'rtl', 'style' => 'min-height: 16rem;'])
                        ->columnSpanFull(),
                    RichEditor::make('requirements_en')
                        ->label('الشروط بالانكليزية')
                        ->extraInputAttributes(['dir' => 'ltr', 'style' => 'min-height: 16rem;'])
                        ->columnSpanFull(),
                    Toggle::make('status')
                        ->label('الحالة')
                        ->default(true)
                        ->inline(false)
                        ->dehydrateStateUsing(fn (bool $state): string => $state ? 'active' : 'inactive')
                        ->afterStateHydrated(function (Toggle $component, mixed $state): void {
                            $component->state($state === 'active');
                        })
                        ->columnSpanFull(),
                ]),
        ];
    }
}
