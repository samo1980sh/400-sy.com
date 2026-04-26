<?php

namespace App\Filament\Resources\InternalPageHeaders\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class InternalPageHeaderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(): array
    {
        return [
            FileUpload::make('image')
                ->label('صورة الهيدر')
                ->disk('public')
                ->directory(config('company_media.internal_headers.directory'))
                ->visibility('public')
                ->image()
                ->imageEditor()
                ->deleteUploadedFileUsing(fn (string $file): bool => Storage::disk('public')->delete($file))
                ->columnSpanFull(),
        ];
    }
}
