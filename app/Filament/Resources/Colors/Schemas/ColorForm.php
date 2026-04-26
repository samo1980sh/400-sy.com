<?php

namespace App\Filament\Resources\Colors\Schemas;

use App\Models\Color;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ColorForm
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
                        ->label('الاسم بالعربية')
                        ->required(),
                    TextInput::make('name_en')
                        ->label('الاسم بالانكليزية'),
                    TextInput::make('code')
                        ->label('الرمز')
                        ->nullable(),
                    TextInput::make('hex')
                        ->label('Hex')
                        ->nullable(),
                    FileUpload::make('image')
                        ->label('الصورة')
                        ->disk('public')
                        ->directory('colors/images')
                        ->visibility('public')
                        ->deleteUploadedFileUsing(fn (string $file): bool => Storage::disk('public')->delete($file))
                        ->image()
                        ->imageEditor(),
                    TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(fn (): int => ((int) (Color::max('sort_order') ?? 0)) + 1)
                        ->required(),
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
