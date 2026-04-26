<?php

namespace App\Filament\Resources\RecruitmentSettings\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class RecruitmentSettingForm
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
                    Toggle::make('is_enabled')
                        ->label('تفعيل قسم التوظيف')
                        ->default(true)
                        ->inline(false)
                        ->columnSpanFull(),
                    TextInput::make('title_ar')
                        ->label('العنوان بالعربية')
                        ->extraInputAttributes(['dir' => 'rtl'])
                        ->maxLength(255),
                    TextInput::make('title_en')
                        ->label('العنوان بالانكليزية')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->maxLength(255),
                    RichEditor::make('intro_ar')
                        ->label('النص التعريفي بالعربية')
                        ->extraInputAttributes(['dir' => 'rtl', 'style' => 'min-height: 16rem;'])
                        ->columnSpanFull(),
                    RichEditor::make('intro_en')
                        ->label('النص التعريفي بالانكليزية')
                        ->extraInputAttributes(['dir' => 'ltr', 'style' => 'min-height: 16rem;'])
                        ->columnSpanFull(),
                ]),
        ];
    }
}
