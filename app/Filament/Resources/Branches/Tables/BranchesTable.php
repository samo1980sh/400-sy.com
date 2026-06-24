<?php

namespace App\Filament\Resources\Branches\Tables;

use App\Filament\Resources\Branches\Schemas\BranchForm;
use App\Models\Branch;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class BranchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category.name_ar')
                    ->label('التصنيف')
                    ->badge()
                    ->searchable(),
                TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'branch' => 'فرع',
                        'hall' => 'صالة',
                        default => (string) $state,
                    }),
                TextColumn::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->searchable()
                    ->badge(),
                TextColumn::make('name_en')
                    ->label('الاسم بالإنكليزية')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('main_image')
                    ->label('الصورة الرئيسية'),
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
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->filters([
                SelectFilter::make('branch_category_id')
                    ->label('التصنيف')
                    ->relationship('category', 'name_ar')
                    ->preload()
                    ->searchable(),
                SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'branch' => 'فرع',
                        'hall' => 'صالة',
                    ]),
            ])
            ->headerActions([
                Action::make('createBranch')
                    ->label('إضافة فرع / صالة')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('إضافة فرع / صالة')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(BranchForm::components())
                    ->action(function (array $data): void {
                        try {
                            Branch::create($data);

                            Notification::make()
                                ->title('تمت إضافة الفرع / الصالة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة الفرع / الصالة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('editBranch')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل فرع / صالة')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(fn (Branch $record) => BranchForm::components($record))
                    ->fillForm(fn (Branch $record): array => $record->only([
                        'branch_category_id',
                        'type',
                        'name_ar',
                        'name_en',
                        'sort_order',
                        'status',
                        'address_ar',
                        'address_en',
                        'phone',
                        'mobile',
                        'whatsapp',
                        'email',
                        'map_url',
                        'description_ar',
                        'description_en',
                        'main_image',
                        'gallery_images',
                    ]))
                    ->action(function (Branch $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث الفرع / الصالة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الفرع / الصالة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteBranch')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Branch $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف الفرع / الصالة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف الفرع / الصالة.')
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
            ->modifyQueryUsing(fn ($query) => $query->with('category')->orderBy('sort_order')->orderBy('name_ar'));
    }
}
