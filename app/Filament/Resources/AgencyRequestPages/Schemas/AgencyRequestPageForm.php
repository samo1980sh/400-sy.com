<?php

namespace App\Filament\Resources\AgencyRequestPages\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class AgencyRequestPageForm
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
                    TextInput::make('title_ar')
                        ->label('العنوان بالعربية')
                        ->extraInputAttributes(['dir' => 'rtl'])
                        ->columnSpanFull()
                        ->maxLength(255),
                    TextInput::make('title_en')
                        ->label('العنوان بالانكليزية')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->columnSpanFull()
                        ->maxLength(255),
                    RichEditor::make('content_ar')
                        ->label('المحتوى بالعربية')
                        ->extraInputAttributes(['dir' => 'rtl', 'style' => 'min-height: 16rem;'])
                        ->columnSpanFull(),
                    RichEditor::make('content_en')
                        ->label('المحتوى بالانكليزية')
                        ->extraInputAttributes(['dir' => 'ltr', 'style' => 'min-height: 16rem;'])
                        ->columnSpanFull(),
                    RichEditor::make('terms_ar')
                        ->label('شروط طلب الوكالة بالعربية')
                        ->extraInputAttributes(['dir' => 'rtl', 'style' => 'min-height: 16rem;'])
                        ->columnSpanFull(),
                    RichEditor::make('terms_en')
                        ->label('شروط طلب الوكالة بالانكليزية')
                        ->extraInputAttributes(['dir' => 'ltr', 'style' => 'min-height: 16rem;'])
                        ->columnSpanFull(),
                ]),
        ];
    }
}
