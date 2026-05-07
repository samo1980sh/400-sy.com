<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Throwable;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('parent.title_ar')
                    ->label('التصنيف الأب')
                    ->sortable()
                    ->placeholder('تصنيف رئيسي')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title_ar')
                    ->label('العنوان بالعربية')
                    ->searchable()
                    ->badge(),
                TextColumn::make('title_en')
                    ->label('العنوان بالإنكليزية')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('children_count')
                    ->label('الأبناء')
                    ->badge()
                    ->sortable(),
                ImageColumn::make('image')
                    ->label('صورة البطاقة')
                    ->disk('public')
                    ->height(48)
                    ->width(48)
                    ->square()
                    ->toggleable(),
                ImageColumn::make('banner')
                    ->label('البانر')
                    ->disk('public')
                    ->height(48)
                    ->width(96)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('editCategory')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل تصنيف')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(fn (Category $record) => CategoryForm::components($record))
                    ->fillForm(fn (Category $record): array => $record->only([
                        'parent_id',
                        'title_ar',
                        'title_en',
                        'image',
                        'banner',
                        'show_in_home',
                        'slug',
                        'sort_order',
                    ]))
                    ->action(function (Category $record, array $data): void {
                        try {
                            if (! blank($record->parent_id)) {
                                unset($data['image'], $data['banner'], $data['show_in_home']);
                            }

                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث التصنيف بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث التصنيف.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف')
                        ->icon(Heroicon::OutlinedTrash),
                ]),
            ])
            ->recordUrl(function (Category $record): ?string {
                if ((int) $record->children_count > 0) {
                    return CategoryResource::getUrl('index', ['parent' => $record->id]);
                }

                return null;
            });
    }
}
