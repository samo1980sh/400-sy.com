<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImageCatalogService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        $imageCatalog = app(ProductImageCatalogService::class);

        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with([
                    'productColors:id,product_id,color_code,color_name_ar,status,sort_order',
                    'category:id,parent_id,title_ar',
                    'category.parent:id,parent_id,title_ar',
                    'category.parent.parent:id,parent_id,title_ar',
                    'category.parent.parent.parent:id,parent_id,title_ar',
                ])
                ->withCount('wholesaleSeries'))
            ->columns([
                ImageColumn::make('main_image')
                    ->label('الصورة')
                    ->disk('public')
                    ->square()
                    ->imageSize(44)
                    ->checkFileExistence(false)
                    ->state(fn (Product $record): ?string => $imageCatalog->mainImagePath($record))
                    ->toggleable(),
                TextColumn::make('model_no')
                    ->label('الكود')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('title_ar')
                    ->label('الاسم بالعربي')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('title_en')
                    ->label('الاسم بالانكليزي')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('category.title_ar')
                    ->label('التصنيف')
                    ->formatStateUsing(function ($state, $record): string {
                        $trail = $record->category?->breadcrumbTrail()
                            ->pluck('title_ar')
                            ->filter()
                            ->values()
                            ->all();

                        return $trail !== [] ? implode(' > ', $trail) : '-';
                    })
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('price')
                    ->label('السعر بعد الحسم')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ','))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('compare_price')
                    ->label('السعر قبل الحسم')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, '.', ','))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('structure')
                    ->label('التركيب')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('collection')
                    ->label('التشكيلة')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('body_fit')
                    ->label('Body Fit')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('drop_type')
                    ->label('Drop')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_best_seller')
                    ->label('الأكثر مبيعًا')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_new')
                    ->label('جديد')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_special_offer')
                    ->label('عرض خاص')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('show_web')
                    ->label('موقع')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('show_app')
                    ->label('تطبيق')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('show_retail')
                    ->label('زبون')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('show_wholesale')
                    ->label('تاجر')
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('has_wholesale_series')
                    ->label('سيريات الجملة')
                    ->state(fn ($record): bool => (int) ($record->wholesale_series_count ?? 0) > 0)
                    ->boolean()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->columnManager()
            ->deferColumnManager(false)
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->filters([
                SelectFilter::make('category_id')
                    ->label('التصنيف')
                    ->options(fn (): array => Category::breadcrumbOptions())
                    ->searchable()
                    ->preload(),
                SelectFilter::make('body_fit')
                    ->label('Body Fit')
                    ->options(fn (): array => Product::query()
                        ->distinct()
                        ->whereNotNull('body_fit')
                        ->where('body_fit', '<>', '')
                        ->orderBy('body_fit')
                        ->pluck('body_fit', 'body_fit')
                        ->toArray())
                    ->searchable(),
                SelectFilter::make('drop_type')
                    ->label('Drop')
                    ->options(fn (): array => Product::query()
                        ->distinct()
                        ->whereNotNull('drop_type')
                        ->where('drop_type', '<>', '')
                        ->orderBy('drop_type')
                        ->pluck('drop_type', 'drop_type')
                        ->toArray())
                    ->searchable(),
                SelectFilter::make('collection')
                    ->label('التشكيلة')
                    ->options(fn (): array => Product::query()
                        ->whereNotNull('collection')
                        ->where('collection', '<>', '')
                        ->orderBy('collection')
                        ->pluck('collection', 'collection')
                        ->toArray())
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('show_web')
                    ->label('موقع'),
                TernaryFilter::make('show_app')
                    ->label('تطبيق'),
                TernaryFilter::make('show_retail')
                    ->label('زبون'),
                TernaryFilter::make('show_wholesale')
                    ->label('تاجر'),
                TernaryFilter::make('has_wholesale_series')
                    ->label('سيريات الجملة')
                    ->queries(
                        true: fn ($query) => $query->whereHas('wholesaleSeries'),
                        false: fn ($query) => $query->whereDoesntHave('wholesaleSeries'),
                    ),
                TernaryFilter::make('is_active')
                    ->label('الحالة'),
                TernaryFilter::make('is_new')
                    ->label('جديد'),
                TernaryFilter::make('is_special_offer')
                    ->label('عرض خاص'),
            ])
            ->recordActions([
                Action::make('deactivateProduct')
                    ->label('إيقاف العرض')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('إيقاف عرض المنتج')
                    ->modalDescription('سيتم تعطيل المنتج وإخفاؤه من العرض بدون حذفه من قاعدة البيانات.')
                    ->modalSubmitActionLabel('إيقاف العرض')
                    ->visible(fn (Product $record): bool => (bool) $record->is_active)
                    ->action(fn (Product $record): bool => $record->update(['is_active' => false])),
                Action::make('activateProduct')
                    ->label('تفعيل')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('تفعيل المنتج')
                    ->modalDescription('سيتم إعادة تفعيل المنتج للعرض.')
                    ->modalSubmitActionLabel('تفعيل')
                    ->visible(fn (Product $record): bool => ! (bool) $record->is_active)
                    ->action(fn (Product $record): bool => $record->update(['is_active' => true])),
                EditAction::make()
                    ->icon(Heroicon::OutlinedPencilSquare),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('deactivateProducts')
                        ->label('إيقاف العرض')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('إيقاف عرض المنتجات المحددة')
                        ->modalDescription('سيتم تعطيل المنتجات المحددة وإخفاؤها من العرض بدون حذفها.')
                        ->modalSubmitActionLabel('إيقاف العرض')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                    BulkAction::make('activateProducts')
                        ->label('تفعيل')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('تفعيل المنتجات المحددة')
                        ->modalDescription('سيتم إعادة تفعيل المنتجات المحددة.')
                        ->modalSubmitActionLabel('تفعيل')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    DeleteBulkAction::make()
                        ->label('حذف')
                        ->icon(Heroicon::OutlinedTrash),
                ]),
            ]);
    }

}
