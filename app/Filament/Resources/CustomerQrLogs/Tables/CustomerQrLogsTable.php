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
            ->modifyQueryUsing(fn ($query) => $query->with([
                'customer',
                'branchRecord',
                'scannedBy',
                'pointVoucherRedemption',
            ]))
            ->defaultSort('scanned_at', 'desc')
            ->columns([
                TextColumn::make('customer.name')
                    ->label('الزبون')
                    ->searchable(),
                TextColumn::make('account_no')
                    ->label('رقم الحساب')
                    ->searchable()
                    ->copyable()
                    ->extraCellAttributes(['dir' => 'ltr', 'style' => 'text-align: left;']),
                TextColumn::make('mobile')
                    ->label('الموبايل')
                    ->searchable()
                    ->extraCellAttributes(['dir' => 'ltr', 'style' => 'text-align: left;']),
                TextColumn::make('branchRecord.name_ar')
                    ->label('الصالة / الفرع')
                    ->placeholder(fn (CustomerQrLog $record): string => $record->branch ?: '—')
                    ->searchable(),
                TextColumn::make('scannedBy.name')
                    ->label('الموظف')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('action_type')
                    ->label('نوع العملية')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'identify' => 'تعرف على الحساب',
                        'hall_sale' => 'عملية صالة',
                        'scan' => 'مسح قديم',
                        default => (string) $state,
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'identify' => 'info',
                        'hall_sale' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('reference_no')
                    ->label('رقم المرجع')
                    ->searchable()
                    ->copyable()
                    ->extraCellAttributes(['dir' => 'ltr', 'style' => 'text-align: left;']),
                TextColumn::make('sale_amount')
                    ->label('قيمة الفاتورة')
                    ->formatStateUsing(fn ($state): string => self::number($state))
                    ->alignEnd(),
                TextColumn::make('discount_amount')
                    ->label('قيمة الحسم')
                    ->formatStateUsing(fn ($state): string => self::number($state))
                    ->alignEnd(),
                TextColumn::make('net_amount')
                    ->label('الصافي')
                    ->formatStateUsing(fn ($state): string => self::number($state))
                    ->alignEnd(),
                TextColumn::make('points_earned')
                    ->label('نقاط مكتسبة')
                    ->formatStateUsing(fn ($state): string => self::number($state))
                    ->alignEnd(),
                TextColumn::make('points_spent')
                    ->label('نقاط القسيمة')
                    ->formatStateUsing(fn ($state): string => self::number($state))
                    ->alignEnd(),
                TextColumn::make('pointVoucherRedemption.order_no')
                    ->label('قسيمة النقاط')
                    ->placeholder('—')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->extraCellAttributes(['dir' => 'ltr', 'style' => 'text-align: left;']),
                TextColumn::make('is_suspicious')
                    ->label('مريب')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'نعم' : 'لا')
                    ->color(fn ($state): string => $state ? 'danger' : 'success'),
                TextColumn::make('suspicious_reason')
                    ->label('سبب الاشتباه')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),
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

    protected static function number(mixed $value): string
    {
        return number_format((float) $value, 2, '.', ',');
    }
}
