<?php

namespace App\Filament\Resources\Sizes\Schemas;

use App\Models\Size;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class SizeForm
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
                    TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(fn (): int => ((int) (Size::max('sort_order') ?? 0)) + 1),
                    TextInput::make('code')
                        ->label('الرمز'),
                    TextInput::make('name_ar')
                        ->label('الاسم بالعربية'),
                    TextInput::make('name_en')
                        ->label('الاسم بالانكليزية'),
                    Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'active' => 'فعال',
                            'inactive' => 'غير فعال',
                        ])
                        ->default('active'),
                ]),
        ];
    }
}
