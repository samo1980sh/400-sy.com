<?php

namespace App\Filament\Resources\Permissions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class PermissionForm
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
                        ->label('اسم الصلاحية')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('slug')
                        ->label('المعرف')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('group')
                        ->label('المجموعة')
                        ->nullable(),
                    TextInput::make('description')
                        ->label('الوصف')
                        ->columnSpanFull()
                        ->maxLength(1000),
                    Select::make('is_active')
                        ->label('الحالة')
                        ->options([
                            1 => 'فعال',
                            0 => 'غير فعال',
                        ])
                        ->default(1),
                ]),
        ];
    }
}
