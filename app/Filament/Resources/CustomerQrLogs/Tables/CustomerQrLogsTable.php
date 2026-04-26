<?php

namespace App\Filament\Resources\CustomerQrLogs\Tables;

use App\Models\CustomerQrLog;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerQrLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('scanned_at', 'desc')
            ->columns([
                TextColumn::make('customer.name')
                    ->label('الزبون')
                    ->searchable(),
                TextColumn::make('account_no')
                    ->label('رقم الحساب')
                    ->searchable(),
                TextColumn::make('mobile')
                    ->label('الموبايل')
                    ->searchable(),
                TextColumn::make('branch')
                    ->label('الفرع')
                    ->searchable(),
                TextColumn::make('action_type')
                    ->label('نوع العملية')
                    ->badge(),
                TextColumn::make('is_suspicious')
                    ->label('مريب')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'نعم' : 'لا')
                    ->color(fn ($state): string => $state ? 'danger' : 'success'),
                TextColumn::make('suspicious_reason')
                    ->label('سبب الاشتباه')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
                TextColumn::make('points_earned')
                    ->label('نقاط مكتسبة')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('points_spent')
                    ->label('نقاط مصروفة')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('discount_amount')
                    ->label('قيمة الحسم')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('reference_no')
                    ->label('رقم المرجع')
                    ->searchable(),
                TextColumn::make('scanned_at')
                    ->label('وقت الاستخدام')
                    ->dateTime()
                    ->sortable(),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns));
    }
}
