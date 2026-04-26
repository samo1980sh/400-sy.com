<?php

namespace App\Filament\Resources\CompanyNewsItems\Tables;

use App\Filament\Resources\CompanyNewsItems\Schemas\CompanyNewsItemForm;
use App\Models\CompanyNewsItem;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class CompanyNewsItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'news' => 'خبر',
                        'event' => 'حدث',
                        default => (string) $state,
                    }),
                TextColumn::make('title_ar')
                    ->label('العنوان بالعربية')
                    ->searchable()
                    ->badge(),
                TextColumn::make('title_en')
                    ->label('Title in English')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                ImageColumn::make('main_image')
                    ->label('الصورة الرئيسية'),
                TextColumn::make('event_date')
                    ->label('التاريخ')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->headerActions([
                Action::make('createCompanyNewsItem')
                    ->label('إضافة خبر / حدث')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('إضافة خبر / حدث')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('7xl')
                    ->schema(fn () => CompanyNewsItemForm::components())
                    ->action(function (array $data): void {
                        try {
                            CompanyNewsItem::create($data);

                            Notification::make()
                                ->title('تمت إضافة الخبر / الحدث بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة الخبر / الحدث.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('editCompanyNewsItem')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading(fn (CompanyNewsItem $record): string => 'تعديل: ' . $record->title_ar)
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('7xl')
                    ->schema(fn (CompanyNewsItem $record) => CompanyNewsItemForm::components($record))
                    ->fillForm(fn (CompanyNewsItem $record): array => $record->only([
                        'type',
                        'slug',
                        'title_ar',
                        'title_en',
                        'excerpt_ar',
                        'excerpt_en',
                        'content_ar',
                        'content_en',
                        'event_date',
                        'main_image',
                        'gallery_images',
                        'sort_order',
                        'status',
                    ]))
                    ->action(function (CompanyNewsItem $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث الخبر / الحدث بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الخبر / الحدث.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteCompanyNewsItem')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (CompanyNewsItem $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف الخبر / الحدث بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف الخبر / الحدث.')
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
            ]);
    }
}
