<?php

namespace App\Filament\Resources\CompanySocialLinks\Schemas;

use App\Models\CompanySocialLink;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CompanySocialLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(?CompanySocialLink $record = null): array
    {
        return [
            Select::make('platform_key')
                ->label('المنصة')
                ->options([
                    'facebook' => 'Facebook',
                    'instagram' => 'Instagram',
                    'x' => 'X',
                    'youtube' => 'YouTube',
                    'tiktok' => 'TikTok',
                    'whatsapp' => 'WhatsApp',
                    'snapchat' => 'Snapchat',
                    'linkedin' => 'LinkedIn',
                ])
                ->required()
                ->unique(CompanySocialLink::class, 'platform_key', ignoreRecord: true),
            TextInput::make('url')
                ->label('الرابط')
                ->url()
                ->extraInputAttributes(['dir' => 'ltr'])
                ->columnSpanFull()
                ->maxLength(2048),
            Toggle::make('status')
                ->label('فعال')
                ->default(true)
                ->inline(false)
                ->afterStateHydrated(function (Toggle $component, mixed $state): void {
                    $component->state(in_array($state, ['active', '1', 1, true], true));
                }),
        ];
    }
}
