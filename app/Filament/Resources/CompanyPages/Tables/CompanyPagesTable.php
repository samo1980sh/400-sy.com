<?php

namespace App\Filament\Resources\CompanyPages\Tables;

use App\Filament\Resources\CompanyPages\Schemas\CompanyPageForm;
use App\Models\CompanyPage;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class CompanyPagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title_ar')
                    ->label('العنوان بالعربية')
                    ->searchable()
                    ->badge(),
                TextColumn::make('title_en')
                    ->label('Title in English')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('slug')
                    ->label('الرابط')
                    ->searchable(),
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
                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('createCompanyPage')
                    ->label('إضافة صفحة')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('إضافة صفحة')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(CompanyPageForm::components())
                    ->action(function (array $data): void {
                        try {
                            CompanyPage::create($data);

                            Notification::make()
                                ->title('تمت إضافة الصفحة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة الصفحة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('editCompanyPage')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading(fn (CompanyPage $record): string => 'تعديل: ' . $record->title_ar)
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(fn (CompanyPage $record) => CompanyPageForm::components($record))
                    ->fillForm(fn (CompanyPage $record): array => $record->only([
                        'slug',
                        'title_ar',
                        'title_en',
                        'content_ar',
                        'content_en',
                        'sort_order',
                        'status',
                    ]))
                    ->action(function (CompanyPage $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث الصفحة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الصفحة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteCompanyPage')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (CompanyPage $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف الصفحة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف الصفحة.')
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
