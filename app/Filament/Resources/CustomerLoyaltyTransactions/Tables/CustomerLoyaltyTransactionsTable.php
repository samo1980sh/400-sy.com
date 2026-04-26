<?php

namespace App\Filament\Resources\CustomerLoyaltyTransactions\Tables;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerLoyaltyTransactionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('occurred_at', 'desc')
            ->columns([
                TextColumn::make('customer.name')
                    ->label('الزبون')
                    ->searchable(),
                TextColumn::make('wallet.customer.name')
                    ->label('المحفظة')
                    ->searchable(),
                TextColumn::make('type')
                    ->label('نوع الحركة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'earn' => 'كسب',
                        'spend' => 'صرف',
                        'deduct' => 'خصم',
                        'adjust' => 'تعديل',
                        'expire' => 'انتهاء',
                        'hold' => 'حجز',
                        default => (string) $state,
                    }),
                TextColumn::make('points')
                    ->label('عدد النقاط')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('balance_before')
                    ->label('قبل')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('balance_after')
                    ->label('بعد')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('reference_no')
                    ->label('المرجع')
                    ->searchable(),
                TextColumn::make('occurred_at')
                    ->label('تاريخ الحركة')
                    ->dateTime()
                    ->sortable(),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns));
    }
}
