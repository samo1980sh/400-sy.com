<?php

namespace App\Filament\Resources\CompanyNewsTickerItems\Tables;

use App\Filament\Resources\CompanyNewsTickerItems\Schemas\CompanyNewsTickerItemForm;
use App\Models\CompanyNewsTickerItem;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class CompanyNewsTickerItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('text_ar')
                    ->label('النص بالعربية')
                    ->limit(80)
                    ->searchable(),
                TextColumn::make('text_en')
                    ->label('Text in English')
                    ->limit(80)
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
                Action::make('createCompanyNewsTickerItem')
                    ->label('إضافة شريط')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('إضافة شريط إخباري')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(CompanyNewsTickerItemForm::components())
                    ->action(function (array $data): void {
                        try {
                            $data['status'] = ! empty($data['status']) ? 'active' : 'inactive';
                            $data['sort_order'] = filled($data['sort_order'] ?? null)
                                ? (int) $data['sort_order']
                                : (int) (CompanyNewsTickerItem::query()->max('sort_order') ?? 0) + 1;
                            CompanyNewsTickerItem::create($data);

                            Notification::make()
                                ->title('تمت إضافة الشريط بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة الشريط.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('editCompanyNewsTickerItem')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading(fn (CompanyNewsTickerItem $record): string => 'تعديل: ' . $record->text_ar)
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(fn (CompanyNewsTickerItem $record) => CompanyNewsTickerItemForm::components())
                    ->fillForm(fn (CompanyNewsTickerItem $record): array => $record->only([
                        'text_ar',
                        'text_en',
                        'sort_order',
                        'status',
                    ]))
                    ->action(function (CompanyNewsTickerItem $record, array $data): void {
                        try {
                            $data['status'] = ! empty($data['status']) ? 'active' : 'inactive';
                            $data['sort_order'] = filled($data['sort_order'] ?? null)
                                ? (int) $data['sort_order']
                                : $record->sort_order;
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث الشريط بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الشريط.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteCompanyNewsTickerItem')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (CompanyNewsTickerItem $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف الشريط بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف الشريط.')
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
