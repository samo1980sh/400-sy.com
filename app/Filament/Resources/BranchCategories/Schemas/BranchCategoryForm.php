<?php

namespace App\Filament\Resources\BranchCategories\Schemas;

use App\Models\BranchCategory;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class BranchCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(?BranchCategory $editingRecord = null): array
    {
        return [
            Grid::make()
                ->columns(4)
                ->schema([
                    TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(fn (): int => ((int) (BranchCategory::max('sort_order') ?? 0)) + 1)
                        ->required()
                        ->columnSpan(1),
                    Grid::make()
                        ->columns(2)
                        ->schema([
                            TextInput::make('name_ar')
                                ->label('الاسم بالعربية')
                                ->extraInputAttributes(['dir' => 'rtl'])
                                ->required()
                                ->maxLength(255),
                            TextInput::make('name_en')
                                ->label('الاسم بالإنكليزية')
                                ->extraInputAttributes(['dir' => 'ltr'])
                                ->required()
                                ->maxLength(255),
                        ])
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
