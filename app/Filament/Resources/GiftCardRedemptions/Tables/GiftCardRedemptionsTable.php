<?php

namespace App\Filament\Resources\GiftCardRedemptions\Tables;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GiftCardRedemptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('giftCard.code')
                    ->label('البطاقة')
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->label('اسم الزبون')
                    ->searchable(),
                TextColumn::make('account_no')
                    ->label('رقم الحساب')
                    ->searchable(),
                TextColumn::make('mobile')
                    ->label('رقم الموبايل')
                    ->searchable(),
                TextColumn::make('order_no')
                    ->label('رقم الطلب')
                    ->searchable(),
                TextColumn::make('amount_used')
                    ->label('المستخدم')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('balance_before')
                    ->label('قبل')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('balance_after')
                    ->label('بعد')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'معلقة',
                        'redeemed' => 'مصروفة',
                        'cancelled' => 'ملغاة',
                        default => (string) $state,
                    }),
                TextColumn::make('applied_at')
                    ->label('تاريخ الاستخدام')
                    ->dateTime()
                    ->sortable(),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns));
    }
}
