<?php

namespace App\Filament\Resources\ContactInfoSettings\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class ContactInfoSettingForm
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
                    TextInput::make('company_name_ar')
                        ->label('اسم الشركة بالعربية')
                        ->extraInputAttributes(['dir' => 'rtl'])
                        ->maxLength(255),
                    TextInput::make('company_name_en')
                        ->label('اسم الشركة بالانكليزية')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->maxLength(255),
                    TextInput::make('address_ar')
                        ->label('العنوان بالعربية')
                        ->extraInputAttributes(['dir' => 'rtl'])
                        ->columnSpanFull()
                        ->maxLength(255),
                    TextInput::make('address_en')
                        ->label('العنوان بالانكليزية')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->columnSpanFull()
                        ->maxLength(255),
                    TextInput::make('phone')
                        ->label('الهاتف')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->tel()
                        ->maxLength(255),
                    TextInput::make('mobile')
                        ->label('الموبايل')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->tel()
                        ->maxLength(255),
                    TextInput::make('whatsapp')
                        ->label('واتس')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->tel()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('البريد الإلكتروني')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->email()
                        ->maxLength(255),
                    TextInput::make('map_url')
                        ->label('رابط الخريطة')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->url()
                        ->columnSpanFull()
                        ->maxLength(2048),
                    TextInput::make('facebook_url')
                        ->label('فيسبوك')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->url()
                        ->maxLength(2048),
                    TextInput::make('instagram_url')
                        ->label('انستغرام')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->url()
                        ->maxLength(2048),
                    TextInput::make('x_url')
                        ->label('X')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->url()
                        ->maxLength(2048),
                    TextInput::make('youtube_url')
                        ->label('يوتيوب')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->url()
                        ->maxLength(2048),
                    RichEditor::make('working_hours_ar')
                        ->label('أوقات الدوام بالعربية')
                        ->extraInputAttributes(['dir' => 'rtl', 'style' => 'min-height: 12rem;'])
                        ->columnSpanFull(),
                    RichEditor::make('working_hours_en')
                        ->label('أوقات الدوام بالانكليزية')
                        ->extraInputAttributes(['dir' => 'ltr', 'style' => 'min-height: 12rem;'])
                        ->columnSpanFull(),
                ]),
        ];
    }
}
