<?php

namespace App\Filament\Resources\CustomerServiceSettings\Schemas;

use App\Models\CustomerServiceSetting;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class CustomerServiceSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(?CustomerServiceSetting $record = null): array
    {
        $recordKey = $record?->setting_key;

        return [
            Grid::make()
                ->columns(2)
                ->schema([
                    Select::make('setting_key')
                        ->label('نوع الصفحة')
                        ->options([
                            'membership_card' => 'بطاقة العضوية',
                            'app_400' => 'تطبيق 400',
                            'terms' => 'الشروط والأحكام',
                            'exchange_policy' => 'سياسة التبديل',
                        ])
                        ->required()
                        ->disabled()
                        ->dehydrated()
                        ->columnSpanFull(),
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
                    TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(0)
                        ->required(),
                    Toggle::make('is_active')
                        ->label('فعال')
                        ->default(true),
                    ...static::componentsFor($recordKey),
                ]),
        ];
    }

    protected static function componentsFor(?string $recordKey): array
    {
        return match ($recordKey) {
            'app_400' => [
                TextInput::make('app_ios_url')
                    ->label('رابط iOS')
                    ->url()
                    ->extraInputAttributes(['dir' => 'ltr'])
                    ->maxLength(2048),
                TextInput::make('app_android_url')
                    ->label('رابط Android')
                    ->url()
                    ->extraInputAttributes(['dir' => 'ltr'])
                    ->maxLength(2048),
                TextInput::make('app_direct_url')
                    ->label('رابط مباشر')
                    ->url()
                    ->extraInputAttributes(['dir' => 'ltr'])
                    ->columnSpanFull()
                    ->maxLength(2048),
                RichEditor::make('content_ar')
                    ->label('تفاصيل التطبيق بالعربية')
                    ->extraInputAttributes(['dir' => 'rtl', 'style' => 'min-height: 16rem;'])
                    ->columnSpanFull(),
                RichEditor::make('content_en')
                    ->label('App details in English')
                    ->extraInputAttributes(['dir' => 'ltr', 'style' => 'min-height: 16rem;'])
                    ->columnSpanFull(),
            ],
            'membership_card' => [
                RichEditor::make('content_ar')
                    ->label('نص بطاقة العضوية بالعربية')
                    ->extraInputAttributes(['dir' => 'rtl', 'style' => 'min-height: 16rem;'])
                    ->columnSpanFull(),
                RichEditor::make('content_en')
                    ->label('Membership card text in English')
                    ->extraInputAttributes(['dir' => 'ltr', 'style' => 'min-height: 16rem;'])
                    ->columnSpanFull(),
            ],
            'terms' => [
                RichEditor::make('content_ar')
                    ->label('الشروط والأحكام بالعربية')
                    ->extraInputAttributes(['dir' => 'rtl', 'style' => 'min-height: 18rem;'])
                    ->columnSpanFull(),
                RichEditor::make('content_en')
                    ->label('Terms and conditions in English')
                    ->extraInputAttributes(['dir' => 'ltr', 'style' => 'min-height: 18rem;'])
                    ->columnSpanFull(),
            ],
            'exchange_policy' => [
                RichEditor::make('content_ar')
                    ->label('سياسة التبديل بالعربية')
                    ->extraInputAttributes(['dir' => 'rtl', 'style' => 'min-height: 18rem;'])
                    ->columnSpanFull(),
                RichEditor::make('content_en')
                    ->label('Exchange policy in English')
                    ->extraInputAttributes(['dir' => 'ltr', 'style' => 'min-height: 18rem;'])
                    ->columnSpanFull(),
            ],
            default => [
                RichEditor::make('content_ar')
                    ->label('المحتوى بالعربية')
                    ->extraInputAttributes(['dir' => 'rtl', 'style' => 'min-height: 16rem;'])
                    ->columnSpanFull(),
                RichEditor::make('content_en')
                    ->label('Content in English')
                    ->extraInputAttributes(['dir' => 'ltr', 'style' => 'min-height: 16rem;'])
                    ->columnSpanFull(),
            ],
        };
    }
}
