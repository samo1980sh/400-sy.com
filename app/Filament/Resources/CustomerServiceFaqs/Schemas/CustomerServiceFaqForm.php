<?php

namespace App\Filament\Resources\CustomerServiceFaqs\Schemas;

use App\Models\CustomerServiceFaq;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CustomerServiceFaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(?CustomerServiceFaq $editingRecord = null): array
    {
        return [
            Grid::make()
                ->columns(2)
                ->schema([
                    TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(fn (): int => ((int) (CustomerServiceFaq::max('sort_order') ?? 0)) + 1)
                        ->required(),
                    Toggle::make('is_active')
                        ->label('فعال')
                        ->default(true),
                    TextInput::make('question_ar')
                        ->label('السؤال بالعربية')
                        ->extraInputAttributes(['dir' => 'rtl'])
                        ->required()
                        ->maxLength(255),
                    TextInput::make('question_en')
                        ->label('Question in English')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->maxLength(255),
                    RichEditor::make('answer_ar')
                        ->label('الإجابة بالعربية')
                        ->extraInputAttributes(['dir' => 'rtl', 'style' => 'min-height: 14rem;'])
                        ->columnSpanFull()
                        ->required(),
                    RichEditor::make('answer_en')
                        ->label('Answer in English')
                        ->extraInputAttributes(['dir' => 'ltr', 'style' => 'min-height: 14rem;'])
                        ->columnSpanFull(),
                ]),
        ];
    }
}
