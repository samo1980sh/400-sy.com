<?php

namespace App\Filament\Resources\CustomerQrCodes\Tables;

use App\Filament\Pages\HallQrWorkspace;
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
                    ->label('رقم الحساب / QR')
                    ->searchable()
                    ->copyable()
                    ->extraCellAttributes(['dir' => 'ltr', 'style' => 'text-align: left;']),
                TextColumn::make('customer.mobile')
                    ->label('الموبايل')
                    ->searchable()
                    ->copyable()
                    ->extraCellAttributes(['dir' => 'ltr', 'style' => 'text-align: left;']),
                TextColumn::make('token')
                    ->label('الرمز الداخلي القديم')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->extraCellAttributes(['dir' => 'ltr', 'style' => 'text-align: left;']),
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
            ->headerActions([
                Action::make('openHallQrWorkspace')
                    ->label('فتح شاشة تشغيل الصالة')
                    ->icon(Heroicon::OutlinedBuildingStorefront)
                    ->color('success')
                    ->visible(fn (): bool => HallQrWorkspace::canAccess())
                    ->url(fn (): string => HallQrWorkspace::getUrl()),
            ])
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
                            $record->status === 'active'
                                ? $record->disable()
                                : $record->enable();

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
