<?php

namespace App\Filament\Resources\Branches\Schemas;

use App\Models\Branch;
use App\Models\BranchCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class BranchForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(?Branch $editingRecord = null): array
    {
        return [
            Grid::make()
                ->columns(4)
                ->schema([
                    TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(fn (): int => ((int) (Branch::max('sort_order') ?? 0)) + 1)
                        ->required()
                        ->columnSpan(1),
                    Grid::make()
                        ->columns(2)
                        ->schema([
                            Select::make('branch_category_id')
                                ->label('التصنيف')
                                ->options(fn (): array => BranchCategory::query()
                                    ->orderBy('sort_order')
                                    ->orderBy('name_ar')
                                    ->pluck('name_ar', 'id')
                                    ->all())
                                ->searchable()
                                ->preload()
                                ->required(),
                            Select::make('type')
                                ->label('النوع')
                                ->options([
                                    'branch' => 'فرع',
                                    'hall' => 'صالة',
                                ])
                                ->default('branch')
                                ->required(),
                        ])
                        ->columnSpanFull(),
                    Grid::make()
                        ->columns(2)
                        ->schema([
                            TextInput::make('name_ar')
                                ->label('الاسم بالعربية')
                                ->extraInputAttributes(['dir' => 'rtl'])
                                ->required()
                                ->maxLength(255),
                            TextInput::make('name_en')
                                ->label('الاسم بالإنكليزية')
                                ->extraInputAttributes(['dir' => 'ltr'])
                                ->required()
                                ->maxLength(255),
                        ])
                        ->columnSpanFull(),
                    Grid::make()
                        ->columns(2)
                        ->schema([
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
                        ])
                        ->columnSpanFull(),
                    Grid::make()
                        ->columns(2)
                        ->schema([
                            TextInput::make('whatsapp')
                                ->label('واتساب')
                                ->extraInputAttributes(['dir' => 'ltr'])
                                ->tel()
                                ->maxLength(255),
                            TextInput::make('email')
                                ->label('البريد الإلكتروني')
                                ->extraInputAttributes(['dir' => 'ltr'])
                                ->email()
                                ->maxLength(255),
                        ])
                        ->columnSpanFull(),
                    TextInput::make('map_url')
                        ->label('رابط الخريطة')
                        ->extraInputAttributes(['dir' => 'ltr'])
                        ->url()
                        ->maxLength(2048)
                        ->columnSpanFull(),
                    Grid::make()
                        ->columns(2)
                        ->schema([
                            Textarea::make('address_ar')
                                ->label('العنوان بالعربية')
                                ->extraInputAttributes(['dir' => 'rtl'])
                                ->rows(3),
                            Textarea::make('address_en')
                                ->label('العنوان بالإنكليزية')
                                ->extraInputAttributes(['dir' => 'ltr'])
                                ->rows(3),
                        ])
                        ->columnSpanFull(),
                    RichEditor::make('description_ar')
                        ->label('الوصف بالعربية')
                        ->extraInputAttributes(['dir' => 'rtl', 'style' => 'min-height: 18rem;'])
                        ->columnSpanFull(),
                    RichEditor::make('description_en')
                        ->label('الوصف بالإنكليزية')
                        ->extraInputAttributes(['dir' => 'ltr', 'style' => 'min-height: 18rem;'])
                        ->columnSpanFull(),
                    FileUpload::make('main_image')
                        ->label('الصورة الرئيسية')
                        ->disk('public')
                        ->directory(config('company_media.branches.main_image.directory'))
                        ->visibility('public')
                        ->image()
                        ->imageEditor()
                        ->deleteUploadedFileUsing(fn (string $file): bool => Storage::disk('public')->delete($file))
                        ->columnSpanFull(),
                    FileUpload::make('gallery_images')
                        ->label('معرض الصور')
                        ->disk('public')
                        ->directory(config('company_media.branches.gallery_image.directory'))
                        ->visibility('public')
                        ->multiple()
                        ->reorderable()
                        ->image()
                        ->imageEditor()
                        ->deleteUploadedFileUsing(fn (string $file): bool => Storage::disk('public')->delete($file))
                        ->columnSpanFull(),
                    Toggle::make('status')
                        ->label('الحالة')
                        ->default(true)
                        ->inline(false)
                        ->dehydrateStateUsing(fn (bool $state): string => $state ? 'active' : 'inactive')
                        ->afterStateHydrated(function (Toggle $component, mixed $state): void {
                            $component->state($state === 'active');
                        })
                        ->columnSpanFull(),
                ]),
        ];
    }
}
