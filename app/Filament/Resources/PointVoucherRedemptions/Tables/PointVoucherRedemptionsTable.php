<?php

namespace App\Filament\Resources\PointVoucherRedemptions\Tables;

use App\Models\PointVoucherRedemption;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Throwable;

class PointVoucherRedemptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['customer', 'voucher.customerGroup']))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('order_no')
                    ->label('رقم / كود القسيمة')
                    ->searchable()
                    ->copyable()
                    ->extraCellAttributes(['dir' => 'ltr', 'style' => 'text-align: left;']),
                TextColumn::make('customer_name')
                    ->label('اسم المستخدم')
                    ->searchable(),
                TextColumn::make('account_no')
                    ->label('رقم الحساب')
                    ->searchable()
                    ->copyable()
                    ->extraCellAttributes(['dir' => 'ltr', 'style' => 'text-align: left;']),
                TextColumn::make('mobile')
                    ->label('رقم الموبايل')
                    ->searchable()
                    ->copyable()
                    ->extraCellAttributes(['dir' => 'ltr', 'style' => 'text-align: left;']),
                TextColumn::make('voucher.name')
                    ->label('القسيمة')
                    ->searchable(),
                TextColumn::make('voucher.code')
                    ->label('كود قالب القسيمة')
                    ->searchable()
                    ->copyable()
                    ->extraCellAttributes(['dir' => 'ltr', 'style' => 'text-align: left;']),
                TextColumn::make('voucher_value')
                    ->label('قيمة القسيمة')
                    ->formatStateUsing(fn ($state): string => self::number($state))
                    ->alignEnd(),
                TextColumn::make('points_spent')
                    ->label('النقاط المصروفة')
                    ->formatStateUsing(fn ($state): string => self::number($state))
                    ->alignEnd(),
                TextColumn::make('usage_method')
                    ->label('طريقة الاستخدام')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::usageMethodLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        'online' => 'info',
                        'in_store' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('branch')
                    ->label('الفرع')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'available' => 'success',
                        'redeemed' => 'info',
                        'expired' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('issued_at')
                    ->label('تاريخ الإصدار')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('تاريخ الانتهاء')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->recordActions([
                self::detailsAction(),
                self::statusAction(),
            ]);
    }

    protected static function detailsAction(): Action
    {
        return Action::make('viewRedemption')
            ->label('التفاصيل')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->modalHeading(fn (PointVoucherRedemption $record): string => 'تفاصيل صرف القسيمة ' . $record->order_no)
            ->modalContent(fn (PointVoucherRedemption $record): View => view(
                'filament.point-voucher-redemptions.details',
                ['redemption' => $record->loadMissing(['customer', 'voucher.customerGroup'])]
            ))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('إغلاق');
    }

    protected static function statusAction(): Action
    {
        return Action::make('updateStatus')
            ->label('تحديث الحالة')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('warning')
            ->modalHeading('تحديث حالة صرف قسيمة النقاط')
            ->modalSubmitActionLabel('حفظ الحالة')
            ->schema([
                Select::make('status')
                    ->label('الحالة')
                    ->options(self::statusOptions())
                    ->required(),
                Textarea::make('notes')
                    ->label('ملاحظات الإدارة')
                    ->rows(4),
            ])
            ->fillForm(fn (PointVoucherRedemption $record): array => [
                'status' => $record->status,
                'notes' => $record->notes,
            ])
            ->action(function (PointVoucherRedemption $record, array $data): void {
                try {
                    $record->update([
                        'status' => (string) ($data['status'] ?? $record->status),
                        'notes' => $data['notes'] ?? $record->notes,
                    ]);

                    Notification::make()
                        ->title('تم تحديث حالة صرف القسيمة.')
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('فشل تحديث حالة صرف القسيمة.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function usageMethodLabel(?string $state): string
    {
        return match ($state) {
            'online' => 'الصرف عبر الموقع',
            'in_store' => 'الصرف داخل الصالات',
            default => filled($state) ? (string) $state : '—',
        };
    }

    protected static function statusOptions(): array
    {
        return [
            'pending' => 'معلقة',
            'available' => 'متاحة',
            'redeemed' => 'مصروفة',
            'expired' => 'منتهية',
            'cancelled' => 'ملغاة',
        ];
    }

    protected static function statusLabel(?string $state): string
    {
        return self::statusOptions()[$state] ?? (filled($state) ? (string) $state : '—');
    }

    protected static function number(mixed $value): string
    {
        return number_format((float) $value, 2, '.', ',');
    }
}
