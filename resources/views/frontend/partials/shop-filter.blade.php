@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';

    $categories = collect($filter_categories ?? []);
    $selectedCategorySlugs = array_values(array_filter((array) ($selected_category_slugs ?? [])));
    $selectedColors = array_values(array_filter((array) ($selected_colors ?? [])));
    $selectedSizes = array_values(array_filter((array) ($selected_sizes ?? [])));
    $selectedBodyFit = array_values(array_filter((array) ($selected_body_fit ?? [])));
    $selectedDropType = array_values(array_filter((array) ($selected_drop_type ?? [])));

    $priceStats = $filter_price_stats ?? [];
    $priceCurrency = (string) ($priceStats['currency'] ?? 'SYP');
    $priceUpperLimit = max(1, (int) ($priceStats['max_limit'] ?? 1));
    $priceMinValue = min($priceUpperLimit, max(0, (int) ($priceStats['selected_min'] ?? 0)));
    $priceMaxValue = min($priceUpperLimit, max($priceMinValue, (int) ($priceStats['selected_max'] ?? $priceUpperLimit)));

    $filterAction = request()->url();
    $queryWithoutPage = request()->except(['page', 'min_price', 'max_price', 'price', 'color', 'colors', 'size', 'sizes', 'body_fit', 'drop_type', 'category', 'categories', 'filter_ajax', 'load_more', 'sort']);
    $resetUrl = $filter_reset_url ?? request()->url();

    $categoryLabel = function ($category) use ($isArabic) {
        return $isArabic
            ? ($category->title_ar ?: $category->title_en ?: $category->slug)
            : ($category->title_en ?: $category->title_ar ?: $category->slug);
    };

    $colorOptions = collect($filter_color_options ?? []);
    $sizeOptions = collect($filter_size_options ?? []);
    $bodyFitOptions = collect($filter_body_fit_options ?? []);
    $dropOptions = collect($filter_drop_options ?? []);

    $colorClassFromValue = function (?string $value): string {
        $normalized = \Illuminate\Support\Str::of((string) $value)
            ->lower()
            ->replace(['_', '/', '\\'], '-')
            ->replaceMatches('/\s+/', '-')
            ->trim('-')
            ->value();

        $map = [
            'beige' => 'bg_beige',
            'black' => 'bg_dark',
            'blue' => 'bg_blue-2',
            'brown' => 'bg_brown',
            'cream' => 'bg_cream',
            'dark-beige' => 'bg_dark-beige',
            'dark-blue' => 'bg_dark-blue',
            'dark-green' => 'bg_dark-green',
            'dark-grey' => 'bg_dark-grey',
            'dark-gray' => 'bg_dark-grey',
            'grey' => 'bg_grey',
            'gray' => 'bg_grey',
            'light-blue' => 'bg_light-blue',
            'light-green' => 'bg_light-green',
            'light-grey' => 'bg_light-grey',
            'light-gray' => 'bg_light-grey',
            'light-pink' => 'bg_light-pink',
            'light-purple' => 'bg_purple',
            'purple' => 'bg_purple',
            'light-yellow' => 'bg_light-yellow',
            'orange' => 'bg_orange',
            'pink' => 'bg_pink',
            'taupe' => 'bg_taupe',
            'white' => 'bg_white',
            'yellow' => 'bg_yellow',
            'berries' => 'bg_pink',
            'red' => 'bg_pink',
            'green' => 'bg_dark-green',
        ];

        return $map[$normalized] ?? '';
    };

    $renderCategoryItems = function ($items) use (&$renderCategoryItems, $selectedCategorySlugs, $categoryLabel) {
        $html = '';

        foreach (collect($items) as $category) {
            $categorySelected = in_array((string) $category->slug, $selectedCategorySlugs, true);
            $isSelectableLeaf = (bool) ($category->is_selectable_leaf ?? false);

            $html .= '<li class="cate-item ' . ($categorySelected ? 'active' : '') . '">';
            $html .= '<div class="list-item d-flex gap-12 align-items-center">';

            if ($isSelectableLeaf) {
                $html .= '<input type="checkbox" name="category[]" class="tf-check" id="category-' . e((string) $category->id) . '" value="' . e((string) $category->slug) . '"' . ($categorySelected ? ' checked' : '') . '>';
                $html .= '<label for="category-' . e((string) $category->id) . '" class="label">';
            } else {
                $html .= '<span class="label">';
            }

            $html .= '<span>' . e($categoryLabel($category)) . '</span>';

            if (isset($category->products_count)) {
                $html .= '&nbsp;<span>(' . e((string) $category->products_count) . ')</span>';
            }

            $html .= $isSelectableLeaf ? '</label>' : '</span>';
            $html .= '</div>';
            $html .= '</li>';

            if ($category->relationLoaded('children') && $category->children->isNotEmpty()) {
                $html .= $renderCategoryItems($category->children);
            }
        }

        return $html;
    };
@endphp

<div class="offcanvas offcanvas-start canvas-filter" id="filterShop" data-shop-filter>
    <div class="canvas-wrapper">
        <header class="canvas-header">
            <div class="filter-icon">
                <span class="icon icon-filter"></span>
                <span>{{ $isArabic ? 'فلتر' : 'Filter' }}</span>
            </div>
            <span class="icon-close icon-close-popup" data-bs-dismiss="offcanvas" aria-label="Close"></span>
        </header>
        <div class="canvas-body">
            <form action="{{ $filterAction }}" id="facet-filter-form" class="facet-filter-form" method="GET" data-filter-form>
                @foreach ($queryWithoutPage as $key => $value)
                    @if (is_array($value))
                        @foreach ($value as $item)
                            <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <input type="hidden" name="grid" value="{{ $selected_grid ?? 'grid-4' }}" data-grid-input>

                @if ($categories->isNotEmpty())
                    <div class="widget-facet wd-categories">
                        <div class="facet-title" data-bs-target="#categories" data-bs-toggle="collapse" aria-expanded="true" aria-controls="categories">
                            <span>{{ $isArabic ? 'تصنيفات المنتجات' : 'Product categories' }}</span>
                            <span class="icon icon-arrow-up"></span>
                        </div>
                        <div id="categories" class="collapse show">
                            <ul class="list-categoris current-scrollbar mb_36">
                                {!! $renderCategoryItems($categories) !!}
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="widget-facet">
                    <div class="facet-title" data-bs-target="#price" data-bs-toggle="collapse" aria-expanded="true" aria-controls="price">
                        <span>{{ $isArabic ? 'السعر' : 'Price' }}</span>
                        <span class="icon icon-arrow-up"></span>
                    </div>
                    <div id="price" class="collapse show">
                        <div class="widget-price filter-price">
                            <div class="tow-bar-block">
                                <div class="progress-price" style="left: {{ ($priceMinValue / max(1, $priceUpperLimit)) * 100 }}%; right: {{ 100 - (($priceMaxValue / max(1, $priceUpperLimit)) * 100) }}%;"></div>
                            </div>
                            <div class="range-input">
                                <input class="range-min" type="range" name="min_price" min="0" max="{{ $priceUpperLimit }}" value="{{ $priceMinValue }}" data-default-value="{{ max(0, (int) ($priceStats['min_limit'] ?? 0)) }}" />
                                <input class="range-max" type="range" name="max_price" min="0" max="{{ $priceUpperLimit }}" value="{{ $priceMaxValue }}" data-default-value="{{ $priceUpperLimit }}" />
                            </div>
                            <div class="box-title-price">
                                <span class="title-price">{{ $isArabic ? 'السعر :' : 'Price :' }}</span>
                                <div class="caption-price">
                                    <div>
                                        <span class="min-price">{{ $priceMinValue }}</span>
                                        <span>{{ $priceCurrency }}</span>
                                    </div>
                                    <span>-</span>
                                    <div>
                                        <span class="max-price">{{ $priceMaxValue }}</span>
                                        <span>{{ $priceCurrency }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($colorOptions->isNotEmpty())
                    <div class="widget-facet">
                        <div class="facet-title" data-bs-target="#color" data-bs-toggle="collapse" aria-expanded="true" aria-controls="color">
                            <span>{{ $isArabic ? 'اللون' : 'Color' }}</span>
                            <span class="icon icon-arrow-up"></span>
                        </div>
                        <div id="color" class="collapse show">
                            <ul class="tf-filter-group filter-color current-scrollbar mb_36">
                                @foreach ($colorOptions as $index => $color)
                                    @php
                                        $colorClass = $colorClassFromValue($color['fallback_key'] ?? $color['label'] ?? $color['value'] ?? '');
                                        $colorStyle = '';

                                        if (!empty($color['hex'])) {
                                            $colorStyle = 'background-color: ' . $color['hex'] . ';';
                                        }
                                    @endphp
                                    <li class="list-item d-flex gap-12 align-items-center">
                                        <input
                                            type="checkbox"
                                            name="color[]"
                                            class="tf-check-color{{ $colorClass !== '' ? ' ' . $colorClass : '' }}"
                                            id="color-{{ $index }}"
                                            value="{{ $color['value'] }}"
                                            @checked(!empty($color['selected']))
                                            @if ($colorStyle !== '') style="{{ $colorStyle }}" @endif
                                        >
                                        <label for="color-{{ $index }}" class="label">
                                            <span>{{ $color['label'] }}</span>&nbsp;<span>({{ $color['count'] }})</span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @if ($sizeOptions->isNotEmpty())
                    <div class="widget-facet">
                        <div class="facet-title" data-bs-target="#size" data-bs-toggle="collapse" aria-expanded="true" aria-controls="size">
                            <span>{{ $isArabic ? 'القياس' : 'Size' }}</span>
                            <span class="icon icon-arrow-up"></span>
                        </div>
                        <div id="size" class="collapse show">
                            <ul class="tf-filter-group current-scrollbar">
                                @foreach ($sizeOptions as $index => $size)
                                    <li class="list-item d-flex gap-12 align-items-center">
                                        <input type="checkbox" name="size[]" class="tf-check tf-check-size" value="{{ $size['value'] }}" id="size-{{ $index }}" @checked(!empty($size['selected']))>
                                        <label for="size-{{ $index }}" class="label">
                                            <span>{{ $size['label'] }}</span>&nbsp;<span>({{ $size['count'] }})</span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @if ($bodyFitOptions->isNotEmpty())
                    <div class="widget-facet">
                        <div class="facet-title" data-bs-target="#body-fit" data-bs-toggle="collapse" aria-expanded="true" aria-controls="body-fit">
                            <span>Body Fit</span>
                            <span class="icon icon-arrow-up"></span>
                        </div>
                        <div id="body-fit" class="collapse show">
                            <ul class="tf-filter-group current-scrollbar">
                                @foreach ($bodyFitOptions as $index => $option)
                                    <li class="list-item d-flex gap-12 align-items-center">
                                        <input type="checkbox" name="body_fit[]" class="tf-check" value="{{ $option['value'] }}" id="body-fit-{{ $index }}" @checked(!empty($option['selected']))>
                                        <label for="body-fit-{{ $index }}" class="label">
                                            <span>{{ $option['label'] }}</span>&nbsp;<span>({{ $option['count'] }})</span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @if ($dropOptions->isNotEmpty())
                    <div class="widget-facet">
                        <div class="facet-title" data-bs-target="#drop-type" data-bs-toggle="collapse" aria-expanded="true" aria-controls="drop-type">
                            <span>Drop</span>
                            <span class="icon icon-arrow-up"></span>
                        </div>
                        <div id="drop-type" class="collapse show">
                            <ul class="tf-filter-group current-scrollbar">
                                @foreach ($dropOptions as $index => $option)
                                    <li class="list-item d-flex gap-12 align-items-center">
                                        <input type="checkbox" name="drop_type[]" class="tf-check" value="{{ $option['value'] }}" id="drop-type-{{ $index }}" @checked(!empty($option['selected']))>
                                        <label for="drop-type-{{ $index }}" class="label">
                                            <span>{{ $option['label'] }}</span>&nbsp;<span>({{ $option['count'] }})</span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                <div class="d-flex gap-12 mt_20">
                    <button type="submit" class="tf-btn btn-fill animate-hover-btn w-100">
                        <span>{{ $isArabic ? 'تطبيق' : 'Apply' }}</span>
                    </button>
                    <a href="{{ $resetUrl }}" class="tf-btn btn-outline animate-hover-btn w-100">
                        <span>{{ $isArabic ? 'إعادة ضبط' : 'Reset' }}</span>
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
