<?php

namespace App\Filament\Resources\RetailCustomerGroups\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class RetailCustomerGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(): array
    {
        return [
            Grid::make()
                ->columns(1)
                ->schema([
                    TextInput::make('name')
                        ->label('اسم الفئة')
                        ->required()
                        ->maxLength(150)
                        ->unique(ignoreRecord: true),
                ]),
        ];
    }
}
