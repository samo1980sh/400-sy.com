<?php

namespace App\Filament\Resources\PaymentMethods\Tables;

use App\Filament\Resources\PaymentMethods\Schemas\PaymentMethodForm;
use App\Models\PaymentMethod;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class PaymentMethodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name_ar')
                    ->label('الاسم بالعربي')
                    ->searchable(),
                TextColumn::make('name_en')
                    ->label('الاسم بالانكليزي')
                    ->searchable(),
                TextColumn::make('code')
                    ->label('الرمز')
                    ->searchable(),
                IconColumn::make('active')
                    ->label('فعال')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable(),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->headerActions([
                CreateAction::make()
                    ->label('إضافة طريقة دفع')
                    ->modalHeading('إضافة طريقة دفع')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(PaymentMethodForm::components())
                    ->action(function (array $data): void {
                        try {
                            PaymentMethod::create($data);

                            Notification::make()
                                ->title('تمت إضافة طريقة الدفع بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة طريقة الدفع.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->modalHeading('تعديل طريقة دفع')
                    ->modalWidth('5xl')
                    ->schema(PaymentMethodForm::components())
                    ->fillForm(fn (PaymentMethod $record): array => $record->only([
                        'name_ar',
                        'name_en',
                        'code',
                        'active',
                        'notes',
                    ]))
                    ->action(function (PaymentMethod $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث طريقة الدفع بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث طريقة الدفع.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deletePaymentMethod')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (PaymentMethod $record): void {
                        $record->delete();
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
