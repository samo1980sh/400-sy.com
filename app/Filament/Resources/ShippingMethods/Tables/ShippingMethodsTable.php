<?php

namespace App\Filament\Resources\ShippingMethods\Tables;

use App\Filament\Resources\ShippingMethods\Schemas\ShippingMethodForm;
use App\Models\ShippingMethod;
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

class ShippingMethodsTable
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
                TextColumn::make('cost')
                    ->label('الكلفة')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ',')),
                TextColumn::make('delivery_time')
                    ->label('مدة التسليم')
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
                    ->label('إضافة طريقة شحن')
                    ->modalHeading('إضافة طريقة شحن')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(ShippingMethodForm::components())
                    ->action(function (array $data): void {
                        try {
                            ShippingMethod::create($data);

                            Notification::make()
                                ->title('تمت إضافة طريقة الشحن بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة طريقة الشحن.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->modalHeading('تعديل طريقة شحن')
                    ->modalWidth('5xl')
                    ->schema(ShippingMethodForm::components())
                    ->fillForm(fn (ShippingMethod $record): array => $record->only([
                        'name_ar',
                        'name_en',
                        'code',
                        'cost',
                        'delivery_time',
                        'active',
                        'notes',
                    ]))
                    ->action(function (ShippingMethod $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث طريقة الشحن بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث طريقة الشحن.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteShippingMethod')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (ShippingMethod $record): void {
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
