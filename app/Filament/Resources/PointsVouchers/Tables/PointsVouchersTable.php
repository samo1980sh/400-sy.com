<?php

namespace App\Filament\Resources\PointsVouchers\Tables;

use App\Filament\Resources\PointsVouchers\Schemas\PointsVoucherForm;
use App\Models\PointsVoucher;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class PointsVouchersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('الرمز')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                TextColumn::make('customerGroup.name')
                    ->label('فئة الزبون')
                    ->searchable(),
                TextColumn::make('points_required')
                    ->label('النقاط المطلوبة')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('voucher_value')
                    ->label('قيمة القسيمة')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('usage_method')
                    ->label('طريقة الاستخدام')
                    ->searchable(),
                TextColumn::make('branch')
                    ->label('الفرع')
                    ->searchable(),
                TextColumn::make('valid_days')
                    ->label('مدة الصلاحية')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'فعالة',
                        'inactive' => 'غير فعالة',
                        default => (string) $state,
                    }),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->headerActions([
                CreateAction::make()
                    ->label('إضافة قسيمة')
                    ->modalHeading('إضافة قسيمة نقاط')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(PointsVoucherForm::components())
                    ->action(function (array $data): void {
                        try {
                            PointsVoucher::create($data);

                            Notification::make()
                                ->title('تمت إضافة القسيمة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة القسيمة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->modalHeading('تعديل قسيمة نقاط')
                    ->modalWidth('5xl')
                    ->schema(PointsVoucherForm::components())
                    ->fillForm(fn (PointsVoucher $record): array => $record->only([
                        'code',
                        'name',
                        'retail_customer_group_id',
                        'points_required',
                        'voucher_value',
                        'usage_method',
                        'branch',
                        'valid_days',
                        'status',
                        'notes',
                    ]))
                    ->action(function (PointsVoucher $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث القسيمة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث القسيمة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }
}
