<?php

namespace App\Filament\Resources\ProductVariants\Tables;

use App\Filament\Resources\ProductVariants\Schemas\ProductVariantForm;
use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Size;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Throwable;

class ProductVariantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.model_no')
                    ->label('رمز المنتج')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('product.title_ar')
                    ->label('وصف المنتج')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('productColor.color_code')
                    ->label('اللون')
                    ->formatStateUsing(fn ($state, $record): string => trim(($record?->productColor?->color_code ?? '-') . ' — ' . ($record?->productColor?->color_name_ar ?? '-')))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('size.code')
                    ->label('القياس')
                    ->formatStateUsing(fn ($state, $record): string => trim(($record?->size?->code ?? '-') . ' (' . ($record?->size?->name_ar ?? '-') . ')'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('price')
                    ->label('بيع')
                    ->formatStateUsing(fn ($state, $record): string => number_format((float) $state, 2, '.', ',') . ' ' . ($record?->product?->currency_ar ?? 'SYP'))
                    ->sortable(),
                TextColumn::make('compare_price')
                    ->label('كرت')
                    ->formatStateUsing(fn ($state, $record): string => blank($state) ? '-' : number_format((float) $state, 2, '.', ',') . ' ' . ($record?->product?->currency_ar ?? 'SYP'))
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 0, '.', ','))
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
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->filters([
                SelectFilter::make('product_id')
                    ->label('رمز المنتج')
                    ->options(fn (): array => Product::query()
                        ->orderBy('model_no')
                        ->orderBy('title_ar')
                        ->get()
                        ->mapWithKeys(fn (Product $product): array => [
                            $product->id => trim(($product->model_no ?: '-') . ' — ' . ($product->title_ar ?: '-')),
                        ])
                        ->all())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('product_color_id')
                    ->label('اللون')
                    ->options(fn (): array => ProductColor::query()
                        ->get()
                        ->sortBy(fn (ProductColor $productColor): string => (string) ($productColor->color_name_ar ?? ''))
                        ->mapWithKeys(fn (ProductColor $productColor): array => [
                            $productColor->id => trim(($productColor->color_code ?: '-') . ' — ' . ($productColor->color_name_ar ?: '-')),
                        ])
                        ->all())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('size_id')
                    ->label('القياس')
                    ->options(fn (): array => Size::query()
                        ->orderBy('sort_order')
                        ->get()
                        ->mapWithKeys(fn (Size $size): array => [
                            $size->id => trim($size->code . ' (' . ($size->name_ar ?: $size->code) . ')'),
                        ])
                        ->all())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active' => 'فعال',
                        'inactive' => 'غير فعال',
                    ]),
            ])
            ->recordActions([
                Action::make('editProductVariant')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل توافر قياس')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(fn (ProductVariant $record) => ProductVariantForm::components())
                    ->fillForm(fn (ProductVariant $record): array => $record->only([
                        'product_id',
                        'product_color_id',
                        'size_id',
                        'price',
                        'compare_price',
                        'quantity',
                        'status',
                    ]))
                    ->action(function (ProductVariant $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث التوافر بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث التوافر.')
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
