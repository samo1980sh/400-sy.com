<?php

namespace App\Filament\Resources\WarehouseUsers\Tables;

use App\Filament\Resources\WarehouseUsers\Schemas\WarehouseUserForm;
use App\Models\WarehouseUser;
use App\Services\WarehouseExportService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class WarehouseUsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_no')
                    ->label('رقم الحساب')
                    ->searchable(),

                TextColumn::make('username')
                    ->label('اسم المستخدم')
                    ->searchable(),

                TextColumn::make('name')
                    ->label('الاسم الكامل')
                    ->searchable(),

                TextColumn::make('mobile')
                    ->label('رقم الموبايل')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'فعال',
                        'inactive' => 'غير فعال',
                        default => (string) $state,
                    }),

                TextColumn::make('account_type')
                    ->label('الصنف')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'point_of_sale' => 'نقطة بيع',
                        'wholesale_customer' => 'زبون جملة',
                        'sales_manager' => 'مدير مبيعات',
                        default => (string) $state,
                    }),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('createWarehouseUser')
                    ->label('إضافة مستخدم')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('إضافة مستخدم مستودع')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(WarehouseUserForm::components())
                    ->action(function (array $data): void {
                        try {
                            WarehouseUser::create($data);

                            Notification::make()
                                ->title('تمت إضافة المستخدم بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة المستخدم.')
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
                Action::make('editWarehouseUser')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل مستخدم مستودع')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(WarehouseUserForm::components())
                    ->fillForm(fn (WarehouseUser $record): array => $record->only([
                        'account_no',
                        'username',
                        'country',
                        'account_type',
                        'name',
                        'mobile',
                        'secondary_mobile',
                        'email',
                        'status',
                        'notes',
                    ]))
                    ->action(function (WarehouseUser $record, array $data): void {
                        try {
                            if (empty($data['password'])) {
                                unset($data['password']);
                            }

                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث المستخدم بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث المستخدم.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('deleteWarehouseUser')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (WarehouseUser $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف المستخدم بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف المستخدم.')
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
