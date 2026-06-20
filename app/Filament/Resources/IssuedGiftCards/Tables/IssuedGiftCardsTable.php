<?php

namespace App\Filament\Resources\IssuedGiftCards\Tables;

use App\Models\GiftCard;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class IssuedGiftCardsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'request',
                'customer',
                'redemptionBranch',
            ]))
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('الرمز')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('request.request_no')
                    ->label('طلب الإصدار')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('الزبون')
                    ->searchable(),
                TextColumn::make('display_name')
                    ->label('الاسم الظاهر')
                    ->placeholder('مجهول'),
                TextColumn::make('recipient_name')
                    ->label('المستفيد')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('القيمة')
                    ->formatStateUsing(fn ($state, GiftCard $record): string => self::money($state, $record->currency)),
                TextColumn::make('balance')
                    ->label('الرصيد')
                    ->formatStateUsing(fn ($state, GiftCard $record): string => self::money($state, $record->currency)),
                TextColumn::make('redemptionBranch.name_ar')
                    ->label('فرع الصرف'),
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
                TextColumn::make('expires_at')
                    ->label('تاريخ الانتهاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->recordActions([
                Action::make('manageGiftCard')
                    ->label('إدارة البطاقة')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('إدارة بطاقة الهدية الصادرة')
                    ->modalSubmitActionLabel('حفظ')
                    ->schema([
                        Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'active' => 'فعالة',
                                'used' => 'مستخدمة',
                                'expired' => 'منتهية',
                                'cancelled' => 'ملغاة',
                            ])
                            ->required(),
                        DateTimePicker::make('expires_at')
                            ->label('تاريخ الانتهاء'),
                        Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(4),
                    ])
                    ->fillForm(fn (GiftCard $record): array => $record->only([
                        'status',
                        'expires_at',
                        'notes',
                    ]))
                    ->action(function (GiftCard $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث بطاقة الهدية.')
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
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف المحدد'),
                ]),
            ]);
    }

    protected static function money(mixed $value, ?string $currency): string
    {
        return number_format((float) $value, 2, '.', ',') . ' ' . strtoupper((string) ($currency ?: 'SYP'));
    }
}
