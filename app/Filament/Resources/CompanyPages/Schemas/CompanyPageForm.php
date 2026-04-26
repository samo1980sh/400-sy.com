<?php

namespace App\Filament\Resources\CompanyPages\Schemas;

use App\Models\CompanyPage;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CompanyPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(?CompanyPage $record = null): array
    {
        return [
            Grid::make()
                ->columns(2)
                ->schema([
                    TextInput::make('slug')
                        ->label('الرابط')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->required()
                        ->unique(CompanyPage::class, 'slug', ignoreRecord: true)
                        ->maxLength(255),
                    TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(0)
                        ->required(),
                    TextInput::make('title_ar')
                        ->label('العنوان بالعربية')
                        ->extraInputAttributes(['dir' => 'rtl'])
                        ->required()
                        ->maxLength(255),
                    TextInput::make('title_en')
                        ->label('Title in English')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->required()
                        ->maxLength(255),
                    Toggle::make('status')
                        ->label('فعال')
                        ->default(true)
                        ->inline(false)
                        ->dehydrateStateUsing(fn (bool $state): string => $state ? 'active' : 'inactive')
                        ->afterStateHydrated(function (Toggle $component, mixed $state): void {
                            $component->state($state === 'active');
                        }),
                    RichEditor::make('content_ar')
                        ->label('المحتوى بالعربية')
                        ->extraInputAttributes(['dir' => 'rtl', 'style' => 'min-height: 16rem;'])
                        ->columnSpanFull(),
                    RichEditor::make('content_en')
                        ->label('Content in English')
                        ->extraInputAttributes(['dir' => 'ltr', 'style' => 'min-height: 16rem;'])
                        ->columnSpanFull(),
                ]),
        ];
    }
}
