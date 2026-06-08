<?php

namespace App\Filament\Resources\Traders\Tables;

use App\Filament\Resources\Traders\Schemas\TraderForm;
use App\Models\Trader;
use App\Models\WholesaleCustomerGroup;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class TradersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_no')
                    ->label('رقم الحساب')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('اسم التاجر')
                    ->searchable(),
                TextColumn::make('wholesaleCustomerGroup.name_ar')
                    ->label('فئة التاجر')
                    ->badge()
                    ->searchable(),
                TextColumn::make('mobile')
                    ->label('رقم الموبايل')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                TextColumn::make('city')
                    ->label('المدينة')
                    ->searchable(),
                TextColumn::make('area')
                    ->label('المنطقة')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'فعال',
                        'inactive' => 'غير فعال',
                        default => (string) $state,
                    }),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('editTrader')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل تاجر')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(TraderForm::components())
                    ->fillForm(fn (Trader $record): array => $record->only([
                        'account_no',
                        'name',
                        'mobile',
                        'secondary_mobile',
                        'email',
                        'wholesale_customer_group_id',
                        'city',
                        'area',
                        'address_line',
                        'status',
                        'notes',
                    ]))
                    ->action(function (Trader $record, array $data): void {
                        try {
                            if (empty($data['password'])) {
                                unset($data['password']);
                            }

                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث التاجر بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث التاجر.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteTrader')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Trader $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف التاجر بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف التاجر.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف')
                        ->icon(Heroicon::OutlinedTrash),
                ]),
            ]);
    }
}
