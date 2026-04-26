<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductImageCatalogService;
use Filament\Actions\Action;
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
                EditAction::make()
                    ->icon(Heroicon::OutlinedPencilSquare),
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
