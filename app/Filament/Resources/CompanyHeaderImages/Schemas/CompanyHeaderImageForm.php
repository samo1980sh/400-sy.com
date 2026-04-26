<?php

namespace App\Filament\Resources\CompanyHeaderImages\Schemas;

use App\Models\CompanyHeaderImage;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class CompanyHeaderImageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(?CompanyHeaderImage $record = null): array
    {
        return [
            Grid::make()
                ->columns(2)
                ->schema([
                    TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(fn (): int => ((int) (CompanyHeaderImage::max('sort_order') ?? 0)) + 1)
                        ->required(),
                    Toggle::make('status')
                        ->label('فعال')
                        ->default(true)
                        ->inline(false)
                        ->dehydrateStateUsing(fn (bool $state): string => $state ? 'active' : 'inactive')
                        ->afterStateHydrated(function (Toggle $component, mixed $state): void {
                            $component->state(in_array($state, ['active', '1', 1, true], true));
                        }),
                    FileUpload::make('media')
                        ->label('الملف')
                        ->disk('public')
                        ->directory(config('company_media.header_images.directory'))
                        ->visibility('public')
                        ->acceptedFileTypes([
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'video/mp4',
                        ])
                        ->downloadable()
                        ->deleteUploadedFileUsing(fn (string $file): bool => Storage::disk('public')->delete($file))
                        ->columnSpanFull(),
                ]),
        ];
    }
}
