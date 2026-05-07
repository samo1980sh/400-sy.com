<?php

namespace App\Filament\Resources\ProductWholesaleAvailabilities\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use App\Filament\Resources\ProductWholesaleAvailabilities\Schemas\ProductWholesaleAvailabilityForm;
use App\Models\ProductWholesaleAvailability;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use App\Models\Product;
use App\Models\WholesaleCustomerGroup;
use Throwable;

class ProductWholesaleAvailabilitiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('product.model_no')
                    ->label('رمز المنتج')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('product.title_ar')
                    ->label('اسم المنتج')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('wholesaleColor.color_code')
                    ->label('رمز اللون')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('wholesaleColor.color_name_ar')
                    ->label('لون الجملة')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('wholesaleColor.color_name_en')
                    ->label('لون الجملة بالإنكليزي')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('wholesaleCustomerGroup.name_ar')
                    ->label('فئة التاجر')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('max_quantity')
                    ->label('الكمية العظمى')
                    ->formatStateUsing(fn ($state): string => number_format((int) $state, 0, '.', ','))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->columnManager()
            ->deferColumnManager(false)
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة'))
            ->filters([
                SelectFilter::make('product_id')
                    ->label('رمز المنتج')
                    ->options(fn (): array => Product::query()
                        ->orderBy('model_no')
                        ->get(['id', 'model_no', 'title_ar'])
                        ->mapWithKeys(fn (Product $product): array => [
                            $product->id => trim(implode(' — ', array_filter([
                                $product->model_no,
                                $product->title_ar,
                            ]))),
                        ])
                        ->all())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('wholesale_customer_group_id')
                    ->label('فئة التاجر')
                    ->options(fn (): array => WholesaleCustomerGroup::query()
                        ->orderBy('sort_order')
                        ->orderBy('name_ar')
                        ->get(['id', 'name_ar'])
                        ->pluck('name_ar', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                Action::make('editWholesaleAvailability')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل توافر التاجر')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(ProductWholesaleAvailabilityForm::components())
                    ->fillForm(fn (ProductWholesaleAvailability $record): array => $record->only([
                        'product_id',
                        'product_wholesale_color_id',
                        'wholesale_customer_group_id',
                        'max_quantity',
                    ]))
                    ->action(function (ProductWholesaleAvailability $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث توافر التاجر بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث توافر التاجر.')
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
