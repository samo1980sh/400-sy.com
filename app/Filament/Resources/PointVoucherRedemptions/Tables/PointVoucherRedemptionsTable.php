<?php

namespace App\Filament\Resources\PointVoucherRedemptions\Tables;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PointVoucherRedemptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('order_no')
                    ->label('رقم الطلب')
                    ->searchable(),
                TextColumn::make('customer_name')
                    ->label('اسم المستخدم')
                    ->searchable(),
                TextColumn::make('account_no')
                    ->label('رقم الحساب')
                    ->searchable(),
                TextColumn::make('mobile')
                    ->label('رقم الموبايل')
                    ->searchable(),
                TextColumn::make('voucher_value')
                    ->label('قيمة القسيمة')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('points_spent')
                    ->label('النقاط المصروفة')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('usage_method')
                    ->label('طريقة الاستخدام')
                    ->searchable(),
                TextColumn::make('branch')
                    ->label('الفرع')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
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
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns));
    }
}
