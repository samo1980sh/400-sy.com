<?php

namespace App\Filament\Resources\CustomerAddresses\Tables;

use App\Filament\Resources\CustomerAddresses\Schemas\CustomerAddressForm;
use App\Models\CustomerAddress;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Throwable;

class CustomerAddressesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->when(
                filled(request()->integer('customer_id')),
                fn ($query) => $query->where('customer_id', request()->integer('customer_id'))
            ))
            ->columns([
                TextColumn::make('customer.name')
                    ->label('الزبون')
                    ->searchable(),
                TextColumn::make('label')
                    ->label('اسم العنوان')
                    ->searchable(),
                TextColumn::make('contact_name')
                    ->label('اسم المستلم')
                    ->searchable(),
                TextColumn::make('mobile')
                    ->label('رقم الهاتف')
                    ->searchable(),
                TextColumn::make('city')
                    ->label('المدينة')
                    ->searchable(),
                TextColumn::make('area')
                    ->label('المنطقة')
                    ->searchable(),
                TextColumn::make('address_type')
                    ->label('نوع العنوان')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'home' => 'منزل',
                        'work' => 'عمل',
                        'shipping' => 'شحن',
                        'other' => 'أخرى',
                        default => (string) $state,
                    }),
                IconColumn::make('is_default')
                    ->label('افتراضي')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('الزبون')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->headerActions([
                CreateAction::make()
                    ->label('إضافة عنوان')
                    ->modalHeading('إضافة عنوان')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(CustomerAddressForm::components())
                    ->action(function (array $data): void {
                        try {
                            CustomerAddress::create($data);

                            Notification::make()
                                ->title('تمت إضافة العنوان بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة العنوان.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('تعديل')
                    ->modalHeading('تعديل عنوان')
                    ->modalWidth('5xl')
                    ->schema(CustomerAddressForm::components())
                    ->fillForm(fn (CustomerAddress $record): array => $record->only([
                        'customer_id',
                        'label',
                        'contact_name',
                        'mobile',
                        'city',
                        'area',
                        'address_line',
                        'address_type',
                        'is_default',
                        'notes',
                    ]))
                    ->action(function (CustomerAddress $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث العنوان بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث العنوان.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteAddress')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (CustomerAddress $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف العنوان بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف العنوان.')
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
