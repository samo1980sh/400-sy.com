<?php

namespace App\Filament\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(?Category $editingRecord = null): array
    {
        return [
            Grid::make()
                ->columns(2)
                ->schema([
                    TextInput::make('sort_order')
                        ->label('الترتيب')
                        ->numeric()
                        ->default(fn (): int => ((int) (Category::max('sort_order') ?? 0)) + 1)
                        ->required()
                        ->columnSpanFull(),
                    Select::make('parent_id')
                        ->label('التصنيف الأب')
                        ->options(fn (): array => Category::hierarchyOptions($editingRecord?->getKey()))
                        ->default(fn (): ?int => request()->integer('parent') ?: null)
                        ->disableOptionWhen(function (string|int $value) use ($editingRecord): bool {
                            if (! $editingRecord?->getKey()) {
                                return false;
                            }

                            return in_array((int) $value, Category::blockedSelectionIds($editingRecord->getKey()), true);
                        })
                        ->native()
                        ->placeholder('تصنيف رئيسي'),
                    Toggle::make('show_in_home')
                        ->label('يظهر في الصفحة الرئيسية')
                        ->visible(fn (?Category $record): bool => $record === null || blank($record->parent_id)),
                    TextInput::make('title_ar')
                        ->label('العنوان بالعربية')
                        ->required(),
                    TextInput::make('title_en')
                        ->label('العنوان بالانكليزية')
                        ->required(),
                    FileUpload::make('image')
                        ->label('الصورة')
                        ->disk('public')
                        ->directory('categories/images')
                        ->visibility('public')
                        ->deleteUploadedFileUsing(fn (string $file): bool => Storage::disk('public')->delete($file))
                        ->image()
                        ->imageEditor()
                        ->visible(fn (?Category $record): bool => $record === null || blank($record->parent_id)),
                    FileUpload::make('banner')
                        ->label('البانر')
                        ->disk('public')
                        ->directory('categories/banners')
                        ->visibility('public')
                        ->deleteUploadedFileUsing(fn (string $file): bool => Storage::disk('public')->delete($file))
                        ->image()
                        ->imageEditor()
                        ->visible(fn (?Category $record): bool => $record === null || blank($record->parent_id)),
                    Hidden::make('slug'),
                ]),
        ];
    }
}
