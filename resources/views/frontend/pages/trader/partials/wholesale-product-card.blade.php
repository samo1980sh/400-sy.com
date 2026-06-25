@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $title = $isArabic
        ? ($product->title_ar ?: $product->title_en ?: $product->model_no)
        : ($product->title_en ?: $product->title_ar ?: $product->model_no);

    $categoryTitle = '';
    if ($product->category) {
        $categoryTitle = $isArabic
            ? ($product->category->name_ar ?? $product->category->title_ar ?? $product->category->name ?? '')
            : ($product->category->name_en ?? $product->category->title_en ?? $product->category->name ?? '');
    }

    $availableColors = $product->wholesaleAvailabilities
        ->filter(fn ($availability) => (int) $availability->max_quantity > 0)
        ->values();

    $colorsById = $product->wholesaleColors->keyBy('id');
    $quantityRows = $product->wholesaleQuantities ?? collect();
    $totalAvailableColors = $availableColors->count();
@endphp

<article class="wholesale-product-card">
    <div class="wholesale-product-card__head">
        <span class="wholesale-product-card__badge">
            {{ $isArabic ? 'جملة' : 'Wholesale' }}
        </span>

        @if ($categoryTitle !== '')
            <span class="wholesale-product-card__category">{{ $categoryTitle }}</span>
        @endif
    </div>

    <div>
        <h3 class="wholesale-product-card__title">{{ $title }}</h3>
        <div class="wholesale-product-card__model" dir="ltr">{{ $product->model_no }}</div>
    </div>

    <div class="wholesale-product-card__quantity">
        <span>{{ $isArabic ? 'ألوان متاحة لحسابك' : 'Colors Available for You' }}</span>
        <strong dir="ltr">{{ number_format($totalAvailableColors) }}</strong>
    </div>

    @if ($availableColors->isNotEmpty())
        <div class="wholesale-product-card__colors">
            <div class="wholesale-product-card__colors-label">
                {{ $isArabic ? 'السيريات والكميات حسب اللون' : 'Series and Quantities by Color' }}
            </div>

            <div class="wholesale-product-card__series">
                @foreach ($availableColors as $availability)
                    @php
                        $color = $colorsById->get($availability->product_wholesale_color_id);
                        $colorName = $color
                            ? ($isArabic
                                ? ($color->color_name_ar ?? $color->color_name_en ?? $color->color_code ?? '')
                                : ($color->color_name_en ?? $color->color_name_ar ?? $color->color_code ?? ''))
                            : '';

                        $colorQuantities = $quantityRows
                            ->filter(fn ($row) => (int) $row->product_wholesale_color_id === (int) $availability->product_wholesale_color_id && (int) $row->quantity > 0)
                            ->sortBy(function ($row) {
                                $size = trim((string) $row->size_text);
                                $sizeSort = is_numeric($size)
                                    ? str_pad((string) ((int) $size), 8, '0', STR_PAD_LEFT)
                                    : 'zzzzzzzz'.$size;

                                return str_pad((string) ((int) $row->series_group), 4, '0', STR_PAD_LEFT).'|'.$sizeSort;
                            })
                            ->values();

                        if ($colorQuantities->isEmpty()) {
                            $colorQuantities = $quantityRows
                                ->filter(fn ($row) => blank($row->product_wholesale_color_id) && (int) $row->quantity > 0)
                                ->sortBy(function ($row) {
                                    $size = trim((string) $row->size_text);
                                    $sizeSort = is_numeric($size)
                                        ? str_pad((string) ((int) $size), 8, '0', STR_PAD_LEFT)
                                        : 'zzzzzzzz'.$size;

                                    return str_pad((string) ((int) $row->series_group), 4, '0', STR_PAD_LEFT).'|'.$sizeSort;
                                })
                                ->values();
                        }

                        $seriesGroups = $colorQuantities->groupBy(fn ($row) => (int) $row->series_group)->sortKeys();
                    @endphp

                    <div class="wholesale-product-card__color-block">
                        <div class="wholesale-product-card__color-head">
                            <span class="wholesale-product-card__color-name">
                                {{ $colorName !== '' ? $colorName : ($isArabic ? 'لون جملة' : 'Wholesale Color') }}
                            </span>
                            <span class="wholesale-product-card__availability-limit" dir="ltr">
                                {{ $isArabic ? 'حد المجموعة: ' : 'Group limit: ' }}{{ (int) $availability->max_quantity }}
                            </span>
                        </div>

                        @if ($seriesGroups->isEmpty())
                            <div class="wholesale-product-card__no-series">
                                {{ $isArabic
                                    ? 'لا توجد سيريات مضبوطة لهذا اللون بعد.'
                                    : 'No series quantities have been configured for this color yet.' }}
                            </div>
                        @else
                            @foreach ($seriesGroups as $seriesGroup => $seriesRows)
                                @if ($seriesGroups->count() > 1)
                                    <div class="wholesale-product-card__series-title">
                                        {{ $isArabic ? 'السيريا' : 'Series' }} <span dir="ltr">{{ $seriesGroup }}</span>
                                    </div>
                                @endif

                                <div class="wholesale-product-card__matrix">
                                    <div class="wholesale-product-card__matrix-row wholesale-product-card__matrix-row--sizes">
                                        @foreach ($seriesRows as $row)
                                            <span class="wholesale-product-card__matrix-cell" dir="ltr">{{ $row->size_text }}</span>
                                        @endforeach
                                    </div>
                                    <div class="wholesale-product-card__matrix-row wholesale-product-card__matrix-row--quantities">
                                        @foreach ($seriesRows as $row)
                                            <span class="wholesale-product-card__matrix-cell" dir="ltr">{{ (int) $row->quantity }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <a href="{{ route('front.trader.products.show', $product) }}" class="wholesale-product-card__action">
        {{ $isArabic ? 'تفاصيل المنتج' : 'Product Details' }}
    </a>
</article>
