<?php

namespace App\Filament\Resources\TraderOrders\Tables;

use App\Models\TraderOrder;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class TraderOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['trader.wholesaleCustomerGroup', 'items.wholesaleColor'])->withCount('items'))
            ->columns([
                TextColumn::make('order_no')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('trader.name')
                    ->label('التاجر')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('trader.wholesaleCustomerGroup.name_ar')
                    ->label('فئة التاجر')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('حالة الطلب')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'قيد المراجعة',
                        'confirmed' => 'مؤكد',
                        'shipped' => 'مُشحن',
                        'delivered' => 'مُسلم',
                        'cancelled' => 'ملغى',
                        default => (string) $state,
                    }),
                TextColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'unpaid' => 'غير مدفوع',
                        'paid' => 'مدفوع',
                        default => (string) $state,
                    }),
                TextColumn::make('items_count')
                    ->label('العناصر')
                    ->badge()
                    ->state(fn (TraderOrder $record): int => (int) ($record->items_count ?? 0)),
                TextColumn::make('total_before_discount')
                    ->label('قبل الحسم')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('discount_value')
                    ->label('الحسم')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('shipping_cost')
                    ->label('الشحن')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('viewDetails')
                    ->label('التفاصيل')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->modalHeading('تفاصيل طلب التاجر')
                    ->modalWidth('6xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(fn (TraderOrder $record) => view('filament.trader-orders.order-details', [
                        'order' => $record->load(['trader.wholesaleCustomerGroup', 'items.wholesaleColor']),
                    ])),
                Action::make('viewHistory')
                    ->label('سجل الحالة')
                    ->icon(Heroicon::OutlinedClock)
                    ->color('gray')
                    ->modalHeading('سجل حالة طلب التاجر')
                    ->modalWidth('6xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(fn (TraderOrder $record) => view('filament.trader-orders.order-status-history', [
                        'history' => $record->statusHistory()->with('changedBy')->latest()->get(),
                    ])),
                Action::make('confirmOrder')
                    ->label('تأكيد')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (TraderOrder $record): bool => $record->status === 'pending')
                    ->action(fn (TraderOrder $record) => self::transitionStatus($record, 'confirmed')),
                Action::make('markShipped')
                    ->label('شحن')
                    ->icon(Heroicon::OutlinedTruck)
                    ->color('warning')
                    ->visible(fn (TraderOrder $record): bool => $record->status === 'confirmed')
                    ->action(fn (TraderOrder $record) => self::transitionStatus($record, 'shipped')),
                Action::make('markDelivered')
                    ->label('تسليم')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->visible(fn (TraderOrder $record): bool => $record->status === 'shipped')
                    ->action(fn (TraderOrder $record) => self::transitionStatus($record, 'delivered')),
                Action::make('cancelOrder')
                    ->label('إلغاء')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (TraderOrder $record): bool => ! in_array($record->status, ['delivered', 'cancelled'], true))
                    ->requiresConfirmation()
                    ->action(fn (TraderOrder $record) => self::transitionStatus($record, 'cancelled')),
                Action::make('markPaid')
                    ->label('مدفوع')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->color('success')
                    ->visible(fn (TraderOrder $record): bool => $record->payment_status !== 'paid')
                    ->action(fn (TraderOrder $record) => self::setPaymentStatus($record, 'paid')),
                Action::make('markUnpaid')
                    ->label('غير مدفوع')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->color('gray')
                    ->visible(fn (TraderOrder $record): bool => $record->payment_status !== 'unpaid')
                    ->requiresConfirmation()
                    ->action(fn (TraderOrder $record) => self::setPaymentStatus($record, 'unpaid')),
            ]);
    }

    protected static function transitionStatus(TraderOrder $order, string $status): void
    {
        $fromStatus = $order->status;
        $updates = ['status' => $status];

        if ($status === 'confirmed' && blank($order->confirmed_at)) {
            $updates['confirmed_at'] = now();
        }

        if ($status === 'shipped' && blank($order->shipped_at)) {
            $updates['shipped_at'] = now();
        }

        if ($status === 'delivered' && blank($order->delivered_at)) {
            $updates['delivered_at'] = now();
        }

        if ($status === 'cancelled' && blank($order->cancelled_at)) {
            $updates['cancelled_at'] = now();
        }

        try {
            $order->update($updates);

            self::logHistory(
                order: $order,
                fromStatus: $fromStatus,
                toStatus: $status,
                fromPaymentStatus: null,
                toPaymentStatus: null,
            );

            Notification::make()
                ->title('تم تحديث حالة الطلب.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('فشل تحديث حالة الطلب.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    protected static function setPaymentStatus(TraderOrder $order, string $status): void
    {
        try {
            $fromPaymentStatus = $order->payment_status;
            $updates = ['payment_status' => $status];

            if ($status === 'paid' && blank($order->paid_at)) {
                $updates['paid_at'] = now();
            }

            if ($status === 'unpaid') {
                $updates['paid_at'] = null;
            }

            $order->update($updates);

            self::logHistory(
                order: $order,
                fromStatus: null,
                toStatus: null,
                fromPaymentStatus: $fromPaymentStatus,
                toPaymentStatus: $status,
            );

            Notification::make()
                ->title('تم تحديث حالة الدفع.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('فشل تحديث حالة الدفع.')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    protected static function logHistory(
        TraderOrder $order,
        ?string $fromStatus,
        ?string $toStatus,
        ?string $fromPaymentStatus,
        ?string $toPaymentStatus,
        ?string $note = null,
    ): void {
        $note ??= match (true) {
            filled($fromStatus) || filled($toStatus) => 'تغيير حالة الطلب من ' . ($fromStatus ?? '—') . ' إلى ' . ($toStatus ?? $order->status),
            filled($fromPaymentStatus) || filled($toPaymentStatus) => 'تغيير حالة الدفع من ' . ($fromPaymentStatus ?? '—') . ' إلى ' . ($toPaymentStatus ?? $order->payment_status),
            default => null,
        };

        \App\Models\TraderOrderStatusHistory::create([
            'trader_order_id' => $order->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus ?? $order->status,
            'from_payment_status' => $fromPaymentStatus,
            'to_payment_status' => $toPaymentStatus,
            'note' => $note,
            'changed_by' => auth()->id(),
        ]);
    }
}
