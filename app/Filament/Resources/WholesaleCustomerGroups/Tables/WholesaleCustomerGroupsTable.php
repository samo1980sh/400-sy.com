<?php

namespace App\Filament\Resources\WholesaleCustomerGroups\Tables;

use App\Filament\Resources\WholesaleCustomerGroups\Schemas\WholesaleCustomerGroupForm;
use App\Models\WholesaleCustomerGroup;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class WholesaleCustomerGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order', 'asc')
            ->columns([
                TextColumn::make('name_ar')
                    ->label('الاسم بالعربية')
                    ->searchable(),
                TextColumn::make('name_en')
                    ->label('الاسم بالانكليزية')
                    ->searchable(),
                TextColumn::make('code')
                    ->label('الرمز')
                    ->searchable(),
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'فعال',
                        'inactive' => 'غير فعال',
                        default => (string) $state,
                    }),
            ])
            ->headerActions([
                Action::make('createGroup')
                    ->label('إضافة فئة')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('إضافة فئة تاجر')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(WholesaleCustomerGroupForm::components())
                    ->action(function (array $data): void {
                        try {
                            WholesaleCustomerGroup::create($data);

                            Notification::make()
                                ->title('تمت إضافة الفئة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل إضافة الفئة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('editGroup')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل فئة تاجر')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(WholesaleCustomerGroupForm::components())
                    ->fillForm(fn (WholesaleCustomerGroup $record): array => $record->only([
                        'name_ar',
                        'name_en',
                        'code',
                        'sort_order',
                        'status',
                    ]))
                    ->action(function (WholesaleCustomerGroup $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث الفئة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الفئة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('deleteGroup')
                    ->label('حذف')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (WholesaleCustomerGroup $record): void {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('تم حذف الفئة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل حذف الفئة.')
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
