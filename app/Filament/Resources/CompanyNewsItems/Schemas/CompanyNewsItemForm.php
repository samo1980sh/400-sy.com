<?php

namespace App\Filament\Resources\CompanyNewsItems\Schemas;

use App\Models\CompanyNewsItem;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class CompanyNewsItemForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(?CompanyNewsItem $record = null): array
    {
        return [
            Tabs::make('الأخبار والأحداث')
                ->columnSpanFull()
                ->tabs([
                    Tab::make('البيانات')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    Select::make('type')
                                        ->label('النوع')
                                        ->options([
                                            'news' => 'خبر',
                                            'event' => 'حدث',
                                        ])
                                        ->default('news')
                                        ->required(),
                                    TextInput::make('sort_order')
                                        ->label('الترتيب')
                                        ->numeric()
                                        ->default(fn (): int => ((int) (CompanyNewsItem::max('sort_order') ?? 0)) + 1)
                                        ->required(),
                                    TextInput::make('slug')
                                        ->label('الرابط')
                                        ->extraInputAttributes(['dir' => 'ltr'])
                                        ->required()
                                        ->unique(CompanyNewsItem::class, 'slug', ignoreRecord: true)
                                        ->maxLength(255),
                                    DatePicker::make('event_date')
                                        ->label('تاريخ الخبر / الحدث'),
                                    Toggle::make('status')
                                        ->label('فعال')
                                        ->default(true)
                                        ->inline(false)
                                        ->dehydrateStateUsing(fn (bool $state): string => $state ? 'active' : 'inactive')
                                        ->afterStateHydrated(function (Toggle $component, mixed $state): void {
                                            $component->state($state === 'active');
                                        })
                                        ->columnSpanFull(),
                                ]),
                        ]),
                    Tab::make('العناوين')
                        ->schema([
                            Grid::make(2)
                                ->schema([
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
                                    TextInput::make('excerpt_ar')
                                        ->label('ملخص بالعربية')
                                        ->extraInputAttributes(['dir' => 'rtl'])
                                        ->columnSpanFull()
                                        ->maxLength(255),
                                    TextInput::make('excerpt_en')
                                        ->label('Excerpt in English')
                                        ->extraInputAttributes(['dir' => 'ltr'])
                                        ->columnSpanFull()
                                        ->maxLength(255),
                                ]),
                        ]),
                    Tab::make('المحتوى والصور')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    RichEditor::make('content_ar')
                                        ->label('المحتوى بالعربية')
                                        ->extraInputAttributes(['dir' => 'rtl', 'style' => 'min-height: 16rem;'])
                                        ->columnSpanFull(),
                                    RichEditor::make('content_en')
                                        ->label('Content in English')
                                        ->extraInputAttributes(['dir' => 'ltr', 'style' => 'min-height: 16rem;'])
                                        ->columnSpanFull(),
                                    FileUpload::make('main_image')
                                        ->label('الصورة الرئيسية')
                                        ->disk('public')
                                        ->directory(config('company_media.news_main_image.directory'))
                                        ->visibility('public')
                                        ->image()
                                        ->imageEditor()
                                        ->deleteUploadedFileUsing(fn (string $file): bool => Storage::disk('public')->delete($file))
                                        ->columnSpanFull(),
                                    FileUpload::make('gallery_images')
                                        ->label('معرض الصور')
                                        ->disk('public')
                                        ->directory(config('company_media.news_gallery_image.directory'))
                                        ->visibility('public')
                                        ->multiple()
                                        ->reorderable()
                                        ->image()
                                        ->imageEditor()
                                        ->deleteUploadedFileUsing(fn (string $file): bool => Storage::disk('public')->delete($file))
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ]),
        ];
    }
}
