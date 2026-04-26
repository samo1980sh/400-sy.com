<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Filament\Resources\Coupons\Schemas\CouponForm;
use App\Models\Coupon;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['creator'])->withCount('redemptions'))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('الكود')
                    ->searchable(),
                TextColumn::make('discount_type')
                    ->label('نوع الخصم')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'percent' => 'نسبة مئوية',
                        'fixed' => 'قيمة مالية ثابتة',
                        default => (string) $state,
                    }),
                TextColumn::make('discount_value')
                    ->label('قيمة الخصم')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('currency')
                    ->label('العملة')
                    ->placeholder('—'),
                TextColumn::make('usage_limit_per_customer')
                    ->label('الاستخدام/حساب')
                    ->badge(),
                TextColumn::make('redemptions_count')
                    ->label('عدد الاستخدام')
                    ->badge(),
                TextColumn::make('starts_at')
                    ->label('تاريخ البداية')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('تاريخ النهاية')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'فعال',
                        'inactive' => 'غير فعال',
                        default => (string) $state,
                    }),
                TextColumn::make('creator.name')
                    ->label('تم الإنشاء بواسطة')
                    ->searchable(),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->headerActions([
                CreateAction::make()
                    ->label('إضافة كوبون')
                    ->modalHeading('إضافة كوبون')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(CouponForm::components())
                    ->action(function (array $data): void {
                        try {
                            Coupon::create($data);

                            Notification::make()
                                ->title('تمت إضافة الكوبون بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة الكوبون.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->modalHeading('تعديل كوبون')
                    ->modalWidth('5xl')
                    ->schema(CouponForm::components())
                    ->fillForm(fn (Coupon $record): array => $record->only([
                        'code',
                        'discount_type',
                        'discount_value',
                        'currency',
                        'starts_at',
                        'ends_at',
                        'usage_limit_per_customer',
                        'status',
                        'notes',
                    ]))
                    ->action(function (Coupon $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث الكوبون بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الكوبون.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }
}
