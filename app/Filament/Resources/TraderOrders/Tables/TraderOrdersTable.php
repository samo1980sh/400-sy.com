<?php

namespace App\Filament\Resources\TraderOrders\Tables;

use App\Models\Trader;
use App\Models\TraderOrder;
use App\Models\WholesaleCustomerGroup;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class TraderOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['trader.wholesaleCustomerGroup', 'items.wholesaleColor'])
                ->withCount('items'))
            ->columns([
                TextColumn::make('order_no')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('trader.name')
                    ->label('التاجر')
                    ->searchable()
                    ->sortable()
                    ->description(fn (TraderOrder $record): ?string => $record->trader_account_no_snapshot
                        ? 'رقم الحساب: '.$record->trader_account_no_snapshot
                        : null),

                TextColumn::make('trader_mobile_snapshot')
                    ->label('موبايل التاجر')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('trader.wholesaleCustomerGroup.name_ar')
                    ->label('فئة التاجر')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('حالة الطلب')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::statusLabel($state))
                    ->color(fn (?string $state): string => self::statusColor($state)),

                TextColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::paymentStatusLabel($state))
                    ->color(fn (?string $state): string => self::paymentStatusColor($state)),

                TextColumn::make('items_count')
                    ->label('البنود')
                    ->badge()
                    ->state(fn (TraderOrder $record): int => (int) ($record->items_count ?? 0)),

                TextColumn::make('price_guard')
                    ->label('فحص السعر')
                    ->badge()
                    ->state(fn (TraderOrder $record): string => self::hasZeroPriceIssue($record) ? 'يوجد سعر صفر' : 'سليم')
                    ->color(fn (TraderOrder $record): string => self::hasZeroPriceIssue($record) ? 'danger' : 'success'),

                TextColumn::make('total')
                    ->label('الإجمالي')
                    ->alignEnd()
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),

                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('حالة الطلب')
                    ->options([
                        'pending' => 'قيد المراجعة',
                        'confirmed' => 'مؤكد',
                        'shipped' => 'مشحون',
                        'delivered' => 'مسلم',
                        'cancelled' => 'ملغى',
                    ]),

                SelectFilter::make('payment_status')
                    ->label('حالة الدفع')
                    ->options([
                        'unpaid' => 'غير مدفوع',
                        'paid' => 'مدفوع',
                    ]),

                SelectFilter::make('trader_id')
                    ->label('التاجر')
                    ->options(fn (): array => Trader::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),

                SelectFilter::make('wholesale_customer_group_id')
                    ->label('فئة التاجر')
                    ->options(fn (): array => WholesaleCustomerGroup::query()
                        ->orderBy('name_ar')
                        ->pluck('name_ar', 'id')
                        ->all())
                    ->query(function ($query, array $data) {
                        $value = $data['value'] ?? null;

                        if (blank($value)) {
                            return $query;
                        }

                        return $query->whereHas('trader', fn ($traderQuery) => $traderQuery
                            ->where('wholesale_customer_group_id', $value));
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('viewDetails')
                    ->label('التفاصيل')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('gray')
                    ->slideOver()
                    ->modalHeading(fn (TraderOrder $record): string => 'تفاصيل طلب التاجر: '.$record->order_no)
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(fn (TraderOrder $record) => view('filament.trader-orders.order-details', [
                        'order' => $record->load([
                            'trader.wholesaleCustomerGroup',
                            'items.wholesaleColor',
                        ]),
                    ])),

                Action::make('viewHistory')
                    ->label('سجل الحالة')
                    ->icon(Heroicon::OutlinedClock)
                    ->color('gray')
                    ->slideOver()
                    ->modalHeading(fn (TraderOrder $record): string => 'سجل حالة الطلب: '.$record->order_no)
                    ->modalWidth('5xl')
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function transitionStatus(TraderOrder $order, string $status): void
    {
        if (self::hasZeroPriceIssue($order) && in_array($status, ['confirmed', 'shipped', 'delivered'], true)) {
            Notification::make()
                ->title('لا يمكن معالجة الطلب.')
                ->body('يوجد في الطلب بند بسعر صفر أو إجمالي الطلب صفر. يرجى مراجعة الأسعار قبل تغيير الحالة.')
                ->danger()
                ->send();

            return;
        }

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
        if ($status === 'paid' && self::hasZeroPriceIssue($order)) {
            Notification::make()
                ->title('لا يمكن اعتماد الدفع.')
                ->body('يوجد في الطلب بند بسعر صفر أو إجمالي الطلب صفر. يرجى مراجعة الأسعار قبل تغيير حالة الدفع.')
                ->danger()
                ->send();

            return;
        }

        try {
            $fromPaymentStatus = $order->payment_status;
            $updates = ['payment_status' => $status];

            if ($status === 'paid' && blank($order->paid_at ?? null)) {
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
            filled($fromStatus) || filled($toStatus) => 'تغيير حالة الطلب من '.self::statusLabel($fromStatus).' إلى '.self::statusLabel($toStatus ?? $order->status),
            filled($fromPaymentStatus) || filled($toPaymentStatus) => 'تغيير حالة الدفع من '.self::paymentStatusLabel($fromPaymentStatus).' إلى '.self::paymentStatusLabel($toPaymentStatus ?? $order->payment_status),
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

    protected static function hasZeroPriceIssue(TraderOrder $order): bool
    {
        $order->loadMissing('items');

        if ((float) $order->total <= 0) {
            return true;
        }

        return $order->items->contains(fn ($item): bool => (float) $item->unit_price <= 0 || (float) $item->line_total <= 0);
    }

    protected static function statusLabel(?string $state): string
    {
        return match ($state) {
            'pending' => 'قيد المراجعة',
            'confirmed' => 'مؤكد',
            'shipped' => 'مشحون',
            'delivered' => 'مسلم',
            'cancelled' => 'ملغى',
            null, '' => '—',
            default => (string) $state,
        };
    }

    protected static function statusColor(?string $state): string
    {
        return match ($state) {
            'pending' => 'warning',
            'confirmed' => 'info',
            'shipped' => 'primary',
            'delivered' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    protected static function paymentStatusLabel(?string $state): string
    {
        return match ($state) {
            'unpaid' => 'غير مدفوع',
            'paid' => 'مدفوع',
            null, '' => '—',
            default => (string) $state,
        };
    }

    protected static function paymentStatusColor(?string $state): string
    {
        return match ($state) {
            'paid' => 'success',
            'unpaid' => 'warning',
            default => 'gray',
        };
    }
}
