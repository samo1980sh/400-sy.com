<?php

namespace App\Filament\Resources\GiftCards\Tables;

use App\Models\GiftCard;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class GiftCardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('الرمز')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('الزبون')
                    ->searchable(),
                TextColumn::make('display_name')
                    ->label('الاسم الظاهر')
                    ->searchable(),
                TextColumn::make('recipient_name')
                    ->label('المستفيد')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('القيمة')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('balance')
                    ->label('الرصيد')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'draft' => 'مسودة',
                        'active' => 'فعالة',
                        'used' => 'مستخدمة',
                        'expired' => 'منتهية',
                        'cancelled' => 'ملغاة',
                        default => (string) $state,
                    }),
                TextColumn::make('issued_at')
                    ->label('تاريخ الإصدار')
                    ->dateTime()
                    ->sortable(),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->recordActions([
                Action::make('processGiftCard')
                    ->label('معالجة الطلب')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('معالجة طلب بطاقة الهدية')
                    ->modalSubmitActionLabel('حفظ')
                    ->schema([
                        Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'draft' => 'مسودة',
                                'active' => 'فعالة',
                                'used' => 'مستخدمة',
                                'expired' => 'منتهية',
                                'cancelled' => 'ملغاة',
                            ])
                            ->required(),
                        DateTimePicker::make('issued_at')
                            ->label('تاريخ الإصدار'),
                        DateTimePicker::make('expires_at')
                            ->label('تاريخ الانتهاء'),
                        Textarea::make('notes')
                            ->label('ملاحظات'),
                    ])
                    ->fillForm(fn (GiftCard $record): array => $record->only([
                        'status',
                        'issued_at',
                        'expires_at',
                        'notes',
                    ]))
                    ->action(function (GiftCard $record, array $data): void {
                        try {
                            if (($data['status'] ?? null) === 'active' && blank($data['issued_at'])) {
                                $data['issued_at'] = now();
                            }

                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث حالة بطاقة الهدية.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث بطاقة الهدية.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}
