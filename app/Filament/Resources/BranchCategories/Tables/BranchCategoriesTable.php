<?php

namespace App\Filament\Resources\BranchCategories\Tables;

use App\Filament\Resources\BranchCategories\BranchCategoryResource;
use App\Filament\Resources\BranchCategories\Schemas\BranchCategoryForm;
use App\Models\BranchCategory;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class BranchCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->searchable()
                    ->badge(),
                TextColumn::make('name_en')
                    ->label('الاسم بالانكليزية')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('branches_count')
                    ->label('الأفرع والصالات')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'فعال',
                        'inactive' => 'غير فعال',
                        default => (string) $state,
                    }),
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->headerActions([
                Action::make('createBranchCategory')
                    ->label('إضافة تصنيف')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('إضافة تصنيف')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('3xl')
                    ->schema(BranchCategoryForm::components())
                    ->action(function (array $data): void {
                        try {
                            BranchCategory::create($data);

                            Notification::make()
                                ->title('تمت إضافة التصنيف بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة التصنيف.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('editBranchCategory')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل تصنيف')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('3xl')
                    ->schema(fn (BranchCategory $record) => BranchCategoryForm::components($record))
                    ->fillForm(fn (BranchCategory $record): array => $record->only([
                        'name_ar',
                        'name_en',
                        'sort_order',
                        'status',
                    ]))
                    ->action(function (BranchCategory $record, array $data): void {
                        try {
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
                Action::make('deleteBranchCategory')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (BranchCategory $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف التصنيف بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف التصنيف.')
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
            ->modifyQueryUsing(fn ($query) => $query->withCount('branches')->orderBy('sort_order')->orderBy('name_ar'));
    }
}
