<?php

namespace App\Filament\Resources\CustomerQrCodes\Tables;

use App\Filament\Resources\CustomerQrCodes\Schemas\CustomerQrCodeForm;
use App\Models\CustomerQrCode;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class CustomerQrCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('customer.name')
                    ->label('الزبون')
                    ->searchable(),
                TextColumn::make('customer.account_no')
                    ->label('رقم الحساب')
                    ->searchable(),
                TextColumn::make('token')
                    ->label('الرمز')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'فعال',
                        'inactive' => 'معطل',
                        default => (string) $state,
                    }),
                TextColumn::make('scan_count')
                    ->label('عدد الاستخدامات')
                    ->sortable(),
                TextColumn::make('last_scanned_at')
                    ->label('آخر استخدام')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('generated_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->recordActions([
                Action::make('toggleStatus')
                    ->label(fn (CustomerQrCode $record): string => $record->status === 'active' ? 'تعطيل' : 'تفعيل')
                    ->icon(fn (CustomerQrCode $record) => $record->status === 'active'
                        ? Heroicon::OutlinedNoSymbol
                        : Heroicon::OutlinedCheckCircle)
                    ->color(fn (CustomerQrCode $record): string => $record->status === 'active' ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(function (CustomerQrCode $record): void {
                        try {
                            $record->update([
                                'status' => $record->status === 'active' ? 'inactive' : 'active',
                                'disabled_at' => $record->status === 'active' ? now() : null,
                            ]);

                            Notification::make()
                                ->title('تم تحديث حالة QR بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث حالة QR.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }
}
