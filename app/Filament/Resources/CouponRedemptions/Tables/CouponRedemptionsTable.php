<?php

namespace App\Filament\Resources\CouponRedemptions\Tables;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CouponRedemptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('coupon.code')
                    ->label('الكوبون')
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
                TextColumn::make('discount_amount')
                    ->label('قيمة الخصم')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('currency')
                    ->label('العملة')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'pending' => 'معلقة',
                        'available' => 'متاحة',
                        'redeemed' => 'مصروفة',
                        'expired' => 'منتهية',
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
