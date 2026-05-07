<?php

namespace App\Filament\Resources\WarehouseHalls\Tables;

use App\Filament\Resources\WarehouseHalls\Schemas\WarehouseHallForm;
use App\Models\WarehouseHall;
use App\Services\WarehouseExportService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class WarehouseHallsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('اسم الصالة')
                    ->searchable(),

                TextColumn::make('code')
                    ->label('الرمز')
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

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('createWarehouseHall')
                    ->label('إضافة صالة')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('إضافة صالة')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(WarehouseHallForm::components())
                    ->action(function (array $data): void {
                        try {
                            WarehouseHall::create($data);

                            Notification::make()
                                ->title('تمت إضافة الصالة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة الصالة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('exportWarehouse')
                    ->label('تصدير')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('success')
                    ->action(fn () => app(WarehouseExportService::class)->download()),
            ])
            ->recordActions([
                Action::make('editWarehouseHall')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل صالة')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(WarehouseHallForm::components())
                    ->fillForm(fn (WarehouseHall $record): array => $record->only([
                        'name',
                        'code',
                        'sort_order',
                        'status',
                    ]))
                    ->action(function (WarehouseHall $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث الصالة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الصالة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('deleteWarehouseHall')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (WarehouseHall $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف الصالة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف الصالة.')
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
