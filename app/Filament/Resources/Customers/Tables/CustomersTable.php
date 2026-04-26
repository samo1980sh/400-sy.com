<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Filament\Resources\CustomerAddresses\CustomerAddressResource;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Models\Customer;
use Illuminate\Support\Arr;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_no')
                    ->label('رقم الحساب')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('الاسم الكامل')
                    ->searchable(),
                TextColumn::make('retail_groups')
                    ->label('فئات المفرق')
                    ->badge()
                    ->formatStateUsing(fn ($state, Customer $record): string => $record->retailGroups
                        ->pluck('name')
                        ->filter()
                        ->implode('، '))
                    ->placeholder('—')
                    ->wrap(),
                TextColumn::make('addresses_count')
                    ->label('عدد العناوين')
                    ->badge()
                    ->state(fn (Customer $record): int => (int) $record->addresses()->count()),
                TextColumn::make('mobile')
                    ->label('رقم الموبايل')
                    ->searchable(),
                TextColumn::make('city')
                    ->label('المدينة')
                    ->searchable(),
                TextColumn::make('area')
                    ->label('المنطقة')
                    ->searchable(),
                TextColumn::make('gender')
                    ->label('الجنس')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'male' => 'ذكر',
                        'female' => 'أنثى',
                        default => (string) $state,
                    }),
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
            ->headerActions([
                Action::make('createCustomer')
                    ->label('إضافة زبون')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('إضافة زبون')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(CustomerForm::components())
                    ->action(function (array $data): void {
                        try {
                            $retailGroupIds = collect(Arr::pull($data, 'retail_group_ids', []))
                                ->filter()
                                ->map(fn ($id): int => (int) $id)
                                ->values()
                                ->all();

                            $customer = Customer::create($data);
                            $customer->retailGroups()->sync($retailGroupIds);

                            Notification::make()
                                ->title('تمت إضافة الزبون بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة الزبون.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('manageAddresses')
                    ->label('العناوين')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->color('gray')
                    ->url(fn (Customer $record): string => CustomerAddressResource::getUrl('index', [
                        'customer_id' => $record->getKey(),
                    ])),
                Action::make('editCustomer')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل زبون')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('5xl')
                    ->schema(CustomerForm::components())
                    ->fillForm(fn (Customer $record): array => $record->only([
                        'account_no',
                        'name',
                        'birth_date',
                        'nationality',
                        'mobile',
                        'secondary_mobile',
                        'gender',
                        'city',
                        'area',
                        'job_title',
                        'marital_status',
                        'email',
                        'status',
                        'notes',
                    ]) + [
                        'retail_group_ids' => $record->retailGroups()->pluck('id')->all(),
                    ])
                    ->action(function (Customer $record, array $data): void {
                        try {
                            $retailGroupIds = collect(Arr::pull($data, 'retail_group_ids', []))
                                ->filter()
                                ->map(fn ($id): int => (int) $id)
                                ->values()
                                ->all();

                            if (empty($data['password'])) {
                                unset($data['password']);
                            }

                            $record->update($data);

                            $record->retailGroups()->sync($retailGroupIds);

                            Notification::make()
                                ->title('تم تحديث الزبون بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الزبون.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteCustomer')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Customer $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف الزبون بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف الزبون.')
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
