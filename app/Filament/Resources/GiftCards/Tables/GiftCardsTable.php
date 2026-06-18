<?php

namespace App\Filament\Resources\GiftCards\Tables;

use App\Models\GiftCardRequest;
use App\Services\GiftCardRequestService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Throwable;

class GiftCardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'customer',
                'pickupBranch',
                'shippingMethod',
                'paymentMethod',
                'redemptionBranch',
                'giftCards',
            ]))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('request_no')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('customer.name')
                    ->label('الزبون')
                    ->searchable(),
                TextColumn::make('customer.mobile')
                    ->label('الموبايل')
                    ->searchable(),
                TextColumn::make('card_quantity')
                    ->label('العدد')
                    ->alignCenter(),
                TextColumn::make('card_amount')
                    ->label('قيمة البطاقة')
                    ->formatStateUsing(fn ($state, GiftCardRequest $record): string => self::money($state, $record->currency)),
                TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->formatStateUsing(fn ($state, GiftCardRequest $record): string => self::money($state, $record->currency)),
                TextColumn::make('fulfillment_method')
                    ->label('الاستلام')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => GiftCardRequest::fulfillmentMethodOptions()[$state] ?? (string) $state),
                TextColumn::make('paymentMethod.name_ar')
                    ->label('الدفع'),
                TextColumn::make('payment_status')
                    ->label('حالة الدفع')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => GiftCardRequest::paymentStatusOptions()[$state] ?? (string) $state),
                TextColumn::make('status')
                    ->label('حالة الطلب')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => GiftCardRequest::statusOptions()[$state] ?? (string) $state),
                TextColumn::make('gift_cards_count')
                    ->label('البطاقات الصادرة')
                    ->counts('giftCards')
                    ->alignCenter(),
                TextColumn::make('submitted_at')
                    ->label('تاريخ الطلب')
                    ->dateTime()
                    ->sortable(),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->recordActions([
                self::detailsAction(),
                self::processAction(),
                self::issueAction(),
            ]);
    }

    protected static function detailsAction(): Action
    {
        return Action::make('viewRequest')
            ->label('التفاصيل')
            ->icon(Heroicon::OutlinedEye)
            ->color('gray')
            ->modalHeading(fn (GiftCardRequest $record): string => 'طلب بطاقة الهدية ' . $record->request_no)
            ->modalContent(fn (GiftCardRequest $record): View => view(
                'filament.gift-card-requests.details',
                ['request' => $record->loadMissing([
                    'customer',
                    'pickupBranch',
                    'shippingMethod',
                    'paymentMethod',
                    'redemptionBranch',
                    'giftCards',
                ])]
            ))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('إغلاق');
    }

    protected static function processAction(): Action
    {
        return Action::make('processRequest')
            ->label('معالجة')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('warning')
            ->visible(fn (GiftCardRequest $record): bool => ! in_array($record->status, ['issued', 'completed'], true))
            ->modalHeading('معالجة طلب بطاقة الهدية')
            ->modalSubmitActionLabel('حفظ المعالجة')
            ->schema([
                Select::make('status')
                    ->label('حالة الطلب')
                    ->options([
                        'pending' => 'بانتظار المراجعة',
                        'reviewing' => 'قيد المراجعة',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                        'cancelled' => 'ملغى',
                    ])
                    ->required(),
                Select::make('payment_status')
                    ->label('حالة الدفع')
                    ->options(GiftCardRequest::paymentStatusOptions())
                    ->required(),
                TextInput::make('delivery_fee')
                    ->label('رسوم التوصيل')
                    ->numeric()
                    ->minValue(0)
                    ->required(),
                Textarea::make('admin_notes')
                    ->label('ملاحظات الإدارة')
                    ->rows(4),
            ])
            ->fillForm(fn (GiftCardRequest $record): array => $record->only([
                'status',
                'payment_status',
                'delivery_fee',
                'admin_notes',
            ]))
            ->action(function (GiftCardRequest $record, array $data): void {
                try {
                    $status = (string) ($data['status'] ?? $record->status);
                    $data['reviewed_at'] = $record->reviewed_at ?: now();
                    $data['cancelled_at'] = $status === 'cancelled' ? now() : null;

                    $record->update($data);

                    Notification::make()
                        ->title('تم تحديث طلب بطاقة الهدية.')
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('فشل تحديث طلب بطاقة الهدية.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function issueAction(): Action
    {
        return Action::make('issueGiftCards')
            ->label('إصدار البطاقات')
            ->icon(Heroicon::OutlinedGift)
            ->color('success')
            ->visible(fn (GiftCardRequest $record): bool => $record->status === 'approved' && $record->giftCards->isEmpty())
            ->requiresConfirmation()
            ->modalHeading('إصدار بطاقات الهدية')
            ->modalDescription(fn (GiftCardRequest $record): string => sprintf(
                'سيتم إصدار %d بطاقة، قيمة كل بطاقة %s.',
                (int) $record->card_quantity,
                self::money($record->card_amount, $record->currency)
            ))
            ->modalSubmitActionLabel('إصدار الآن')
            ->schema([
                DateTimePicker::make('expires_at')
                    ->label('تاريخ انتهاء البطاقات'),
                Textarea::make('usage_instructions')
                    ->label('طريقة استخدام البطاقة')
                    ->rows(3),
                Textarea::make('redemption_terms')
                    ->label('شروط الصرف')
                    ->rows(3),
                Textarea::make('notes')
                    ->label('ملاحظات داخلية')
                    ->rows(3),
            ])
            ->action(function (GiftCardRequest $record, array $data): void {
                try {
                    $cards = app(GiftCardRequestService::class)->issueCards($record, $data);

                    Notification::make()
                        ->title('تم إصدار بطاقات الهدية بنجاح.')
                        ->body('عدد البطاقات الصادرة: ' . $cards->count())
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('تعذر إصدار بطاقات الهدية.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected static function money(mixed $value, ?string $currency): string
    {
        return number_format((float) $value, 2, '.', ',') . ' ' . strtoupper((string) ($currency ?: 'SYP'));
    }
}
