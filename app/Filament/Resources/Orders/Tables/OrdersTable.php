<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PaymentMethod;
use App\Services\CustomerLoyaltyService;
use App\Services\OrderCouponService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with(['customer', 'shippingAddress', 'shippingMethod', 'couponRedemption.coupon'])->withCount('items'))
            ->columns([
                TextColumn::make('order_no')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('الزبون')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer_mobile_snapshot')
                    ->label('الموبايل')
                    ->searchable()
                    ->copyable()
                    ->extraAttributes(['dir' => 'ltr'])
                    ->toggleable(),
                TextColumn::make('shippingAddress.label')
                    ->label('عنوان الشحن')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('shippingMethod.name_ar')
                    ->label('طريقة الشحن')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('حالة الطلب')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => self::statusLabels()[$state] ?? (string) $state),
                TextColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'paid' => 'success',
                        'unpaid' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state): string => self::paymentStatusLabels()[$state] ?? (string) $state),
                TextColumn::make('payment_method')
                    ->label('طريقة الدفع')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::paymentMethodLabel($state))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('coupon_code_snapshot')
                    ->label('الكوبون')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('coupon_discount_value')
                    ->label('حسم الكوبون')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('items_count')
                    ->label('العناصر')
                    ->badge()
                    ->state(fn (Order $record): int => (int) ($record->items_count ?? 0)),
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
                IconColumn::make('is_gift')
                    ->label('هدية')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('الزبون')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('حالة الطلب')
                    ->options(self::statusLabels()),
                SelectFilter::make('payment_status')
                    ->label('حالة الدفع')
                    ->options(self::paymentStatusLabels()),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->recordActions([
                Action::make('viewOrder')
                    ->label('التفاصيل')
                    ->icon(Heroicon::OutlinedEye)
                    ->color('primary')
                    ->modalHeading(fn (Order $record): string => 'تفاصيل الطلب ' . $record->order_no)
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(fn (Order $record) => view('filament.orders.order-details', [
                        'order' => $record->load(['customer', 'shippingAddress', 'shippingMethod', 'items']),
                        'paymentMethodLabel' => self::paymentMethodLabel($record->payment_method),
                        'statusLabels' => self::statusLabels(),
                        'paymentStatusLabels' => self::paymentStatusLabels(),
                    ])),
                Action::make('viewHistory')
                    ->label('سجل الحالة')
                    ->icon(Heroicon::OutlinedClock)
                    ->color('gray')
                    ->slideOver()
                    ->modalHeading(fn (Order $record): string => 'سجل حالة الطلب ' . $record->order_no)
                    ->modalWidth('4xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('إغلاق')
                    ->modalContent(fn (Order $record) => view('filament.orders.order-status-history', [
                        'history' => $record->statusHistory()->with('changedBy')->latest('id')->get(),
                        'statusLabels' => self::statusLabels(),
                        'paymentStatusLabels' => self::paymentStatusLabels(),
                    ])),
                Action::make('confirmOrder')
                    ->label('تأكيد')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === 'pending')
                    ->action(fn (Order $record) => self::transitionStatus($record, 'confirmed')),
                Action::make('markShipped')
                    ->label('شحن')
                    ->icon(Heroicon::OutlinedTruck)
                    ->color('warning')
                    ->visible(fn (Order $record): bool => $record->status === 'confirmed')
                    ->action(fn (Order $record) => self::transitionStatus($record, 'shipped')),
                Action::make('markDelivered')
                    ->label('تسليم')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->status === 'shipped')
                    ->action(fn (Order $record) => self::transitionStatus($record, 'delivered')),
                Action::make('cancelOrder')
                    ->label('إلغاء')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (Order $record): bool => ! in_array($record->status, ['delivered', 'cancelled'], true))
                    ->requiresConfirmation()
                    ->action(fn (Order $record) => self::transitionStatus($record, 'cancelled')),
                Action::make('markPaid')
                    ->label('مدفوع')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->color('success')
                    ->visible(fn (Order $record): bool => $record->payment_status !== 'paid')
                    ->action(fn (Order $record) => self::setPaymentStatus($record, 'paid')),
                Action::make('markUnpaid')
                    ->label('غير مدفوع')
                    ->icon(Heroicon::OutlinedCurrencyDollar)
                    ->color('gray')
                    ->visible(fn (Order $record): bool => $record->payment_status !== 'unpaid')
                    ->requiresConfirmation()
                    ->action(fn (Order $record) => self::setPaymentStatus($record, 'unpaid')),
                Action::make('applyCoupon')
                    ->label('تطبيق كوبون')
                    ->icon(Heroicon::OutlinedTicket)
                    ->color('primary')
                    ->visible(fn (Order $record): bool => $record->status !== 'cancelled' && filled($record->customer_id))
                    ->modalHeading('تطبيق كوبون على الطلب')
                    ->modalSubmitActionLabel('تطبيق')
                    ->modalWidth('4xl')
                    ->schema([
                        Select::make('coupon_code')
                            ->label('الكوبون')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->options(fn (): array => Coupon::query()
                                ->where('status', 'active')
                                ->orderBy('code')
                                ->get(['code', 'discount_type', 'discount_value'])
                                ->mapWithKeys(fn (Coupon $coupon): array => [
                                    $coupon->code => trim(implode(' - ', array_filter([
                                        $coupon->code,
                                        $coupon->discount_type === 'percent'
                                            ? $coupon->discount_value . '%'
                                            : number_format((float) $coupon->discount_value, 2, '.', ','),
                                    ]))),
                                ])
                                ->all()),
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->action(function (Order $record, array $data): void {
                        try {
                            app(OrderCouponService::class)->applyCoupon(
                                $record->refresh(),
                                (string) ($data['coupon_code'] ?? ''),
                                $data['notes'] ?? null,
                            );

                            Notification::make()
                                ->title('تم تطبيق الكوبون على الطلب.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تطبيق الكوبون.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }

    /**
     * @return array<string, string>
     */
    protected static function statusLabels(): array
    {
        return [
            'pending' => 'قيد المراجعة',
            'confirmed' => 'مؤكد',
            'shipped' => 'مُشحن',
            'delivered' => 'مُسلم',
            'cancelled' => 'ملغى',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function paymentStatusLabels(): array
    {
        return [
            'unpaid' => 'غير مدفوع',
            'paid' => 'مدفوع',
        ];
    }

    protected static function paymentMethodLabel(?string $code): string
    {
        if (blank($code)) {
            return '—';
        }

        static $labels = null;

        $labels ??= PaymentMethod::query()
            ->pluck('name_ar', 'code')
            ->all();

        return $labels[$code] ?? (string) str($code)
            ->replace('_', ' ')
            ->headline();
    }

    protected static function transitionStatus(Order $order, string $status): void
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

        $order->update($updates);

        self::logHistory(
            order: $order,
            fromStatus: $fromStatus,
            toStatus: $status,
            fromPaymentStatus: null,
            toPaymentStatus: null,
        );

        app(CustomerLoyaltyService::class)->syncForOrder($order->refresh());

        Notification::make()
            ->title('تم تحديث حالة الطلب.')
            ->body('أصبحت الحالة: ' . self::statusLabels()[$status])
            ->success()
            ->send();
    }

    protected static function setPaymentStatus(Order $order, string $status): void
    {
        $fromPaymentStatus = $order->payment_status;
        $updates = ['payment_status' => $status];

        if ($status === 'paid' && blank($order->paid_at)) {
            $updates['paid_at'] = now();
        }

        if ($status === 'unpaid') {
            $updates['paid_at'] = null;
        }

        try {
            $order->update($updates);

            self::logHistory(
                order: $order,
                fromStatus: null,
                toStatus: null,
                fromPaymentStatus: $fromPaymentStatus,
                toPaymentStatus: $status,
            );

            app(CustomerLoyaltyService::class)->syncForOrder($order->refresh());

            Notification::make()
                ->title('تم تحديث حالة الدفع.')
                ->body('أصبحت حالة الدفع: ' . self::paymentStatusLabels()[$status])
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
        Order $order,
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

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus ?? $order->status,
            'from_payment_status' => $fromPaymentStatus,
            'to_payment_status' => $toPaymentStatus,
            'note' => $note,
            'changed_by' => auth()->id(),
        ]);
    }
}
