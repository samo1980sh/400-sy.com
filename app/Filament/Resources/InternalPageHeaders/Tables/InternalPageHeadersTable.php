<?php

namespace App\Filament\Resources\InternalPageHeaders\Tables;

use App\Filament\Resources\InternalPageHeaders\Schemas\InternalPageHeaderForm;
use App\Models\InternalPageHeader;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class InternalPageHeadersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('section_key')
                    ->label('القسم')
                    ->searchable()
                    ->badge(),
                TextColumn::make('title_ar')
                    ->label('العنوان بالعربية')
                    ->searchable(),
                ImageColumn::make('image')
                    ->label('الصورة'),
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'فعال',
                        'inactive' => 'غير فعال',
                        default => (string) $state,
                    }),
            ])
            ->recordActions([
                Action::make('editImage')
                    ->label('تعديل الصورة')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading(fn (InternalPageHeader $record): string => 'تعديل صورة: ' . $record->title_ar)
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('3xl')
                    ->schema(fn (InternalPageHeader $record) => InternalPageHeaderForm::components())
                    ->fillForm(fn (InternalPageHeader $record): array => $record->only(['image']))
                    ->action(function (InternalPageHeader $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث صورة الهيدر بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث صورة الهيدر.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteImage')
                    ->label('حذف الصورة')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (InternalPageHeader $record): void {
                        try {
                            $record->update(['image' => null]);

                            Notification::make()
                                ->title('تم حذف الصورة فقط.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف الصورة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }
}
