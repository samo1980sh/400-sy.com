@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';

    $categories = collect($filter_categories ?? []);
    $selectedCategorySlugs = array_values(array_filter((array) ($selected_category_slugs ?? [])));
    $selectedColors = array_values(array_filter((array) ($selected_colors ?? [])));
    $selectedSizes = array_values(array_filter((array) ($selected_sizes ?? [])));
    $selectedBodyFit = array_values(array_filter((array) ($selected_body_fit ?? [])));
    $selectedDropType = array_values(array_filter((array) ($selected_drop_type ?? [])));
    $selectedCollections = array_values(array_filter((array) ($selected_collections ?? [])));
    $selectedSpecialOffers = array_values(array_filter((array) ($selected_special_offers ?? [])));

    $priceStats = $filter_price_stats ?? [];
    $baseMinLimit = max(0, (int) ($priceStats['base_min_limit'] ?? 0));
    $baseMaxLimit = max(1, (int) ($priceStats['base_max_limit'] ?? 1));
    $selectedMinBase = min($baseMaxLimit, max($baseMinLimit, (int) ($priceStats['selected_min_base'] ?? $baseMinLimit)));
    $selectedMaxBase = min($baseMaxLimit, max($selectedMinBase, (int) ($priceStats['selected_max_base'] ?? $baseMaxLimit)));
    $displayMinLimit = max(0, (int) ($priceStats['display_min_limit'] ?? 0));
    $displayMaxLimit = max(1, (int) ($priceStats['display_max_limit'] ?? 1));
    $selectedMinDisplay = min($displayMaxLimit, max($displayMinLimit, (int) ($priceStats['selected_min_display'] ?? $displayMinLimit)));
    $selectedMaxDisplay = min($displayMaxLimit, max($selectedMinDisplay, (int) ($priceStats['selected_max_display'] ?? $displayMaxLimit)));
    $priceCurrency = (string) ($priceStats['currency'] ?? 'SYP');
    $priceSymbol = (string) ($priceStats['symbol'] ?? $priceCurrency);
    $priceRate = (float) ($priceStats['rate'] ?? 1);
    $priceFilterApplied = filled(request()->query('price')) || filled(request()->query('min_price')) || filled(request()->query('max_price'));

    $filterAction = request()->url();
    $queryWithoutPage = request()->except(['page', 'min_price', 'max_price', 'price', 'color', 'colors', 'size', 'sizes', 'body_fit', 'drop_type', 'collection', 'collections', 'special_offer', 'special_offers', 'category', 'categories', 'filter_ajax', 'load_more', 'sort']);
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
    $collectionOptions = collect($filter_collection_options ?? []);
    $specialOfferOption = $filter_special_offer_option ?? null;

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
                        <div
                            class="widget-price filter-price js-price-filter"
                            data-base-min-limit="{{ $baseMinLimit }}"
                            data-base-max-limit="{{ $baseMaxLimit }}"
                            data-selected-min-base="{{ $selectedMinBase }}"
                            data-selected-max-base="{{ $selectedMaxBase }}"
                            data-currency="{{ $priceCurrency }}"
                            data-symbol="{{ $priceSymbol }}"
                            data-rate="{{ $priceRate }}"
                            data-price-filter-active="{{ $priceFilterApplied ? '1' : '0' }}"
                        >
                            <div class="tow-bar-block">
                                <div class="progress-price" style="left: {{ (($selectedMinDisplay - $displayMinLimit) / max(1, $displayMaxLimit - $displayMinLimit)) * 100 }}%; right: {{ 100 - ((($selectedMaxDisplay - $displayMinLimit) / max(1, $displayMaxLimit - $displayMinLimit)) * 100) }}%;"></div>
                            </div>
                            <input type="hidden" name="min_price" value="{{ $selectedMinBase }}" data-price-base-min-input @disabled(! $priceFilterApplied)>
                            <input type="hidden" name="max_price" value="{{ $selectedMaxBase }}" data-price-base-max-input @disabled(! $priceFilterApplied)>
                            <div class="range-input">
                                <input class="range-min" type="range" min="{{ $displayMinLimit }}" max="{{ $displayMaxLimit }}" value="{{ $selectedMinDisplay }}" data-price-display-min />
                                <input class="range-max" type="range" min="{{ $displayMinLimit }}" max="{{ $displayMaxLimit }}" value="{{ $selectedMaxDisplay }}" data-price-display-max />
                            </div>
                            <div class="box-title-price">
                                <span class="title-price">{{ $isArabic ? 'السعر :' : 'Price :' }}</span>
                                <div class="caption-price">
                                    <div>
                                        <span class="min-price" data-price-min-label>{{ $selectedMinDisplay }}</span>
                                        <span data-price-currency-label>{{ $priceSymbol }}</span>
                                    </div>
                                    <span>-</span>
                                    <div>
                                        <span class="max-price" data-price-max-label>{{ $selectedMaxDisplay }}</span>
                                        <span data-price-currency-label>{{ $priceSymbol }}</span>
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


                @if ($collectionOptions->isNotEmpty())
                    <div class="widget-facet">
                        <div class="facet-title" data-bs-target="#collection-filter" data-bs-toggle="collapse" aria-expanded="true" aria-controls="collection-filter">
                            <span>{{ $isArabic ? 'التشكيلة' : 'Collection' }}</span>
                            <span class="icon icon-arrow-up"></span>
                        </div>
                        <div id="collection-filter" class="collapse show">
                            <ul class="tf-filter-group current-scrollbar">
                                @foreach ($collectionOptions as $index => $option)
                                    <li class="list-item d-flex gap-12 align-items-center">
                                        <input type="checkbox" name="collections[]" class="tf-check" value="{{ $option['value'] }}" id="collection-filter-{{ $index }}" @checked(!empty($option['selected']))>
                                        <label for="collection-filter-{{ $index }}" class="label">
                                            <span>{{ $option['label'] }}</span>&nbsp;<span>({{ $option['count'] }})</span>
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif

                @if (!empty($specialOfferOption))
                    <div class="widget-facet">
                        <div class="facet-title" data-bs-target="#special-offer-filter" data-bs-toggle="collapse" aria-expanded="true" aria-controls="special-offer-filter">
                            <span>{{ $isArabic ? 'العروض' : 'Offers' }}</span>
                            <span class="icon icon-arrow-up"></span>
                        </div>
                        <div id="special-offer-filter" class="collapse show">
                            <ul class="tf-filter-group current-scrollbar">
                                <li class="list-item d-flex gap-12 align-items-center">
                                    <input type="checkbox" name="special_offers[]" class="tf-check" value="offer" id="special-offer-filter-option" @checked(!empty($specialOfferOption['selected']))>
                                    <label for="special-offer-filter-option" class="label">
                                        <span>{{ $specialOfferOption['label'] ?? ($isArabic ? 'عروض خاصة' : 'Special offers') }}</span>&nbsp;<span>({{ $specialOfferOption['count'] ?? 0 }})</span>
                                    </label>
                                </li>
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

@once
    @push('scripts')
        <script>
            (function () {
                var priceFilterForms = document.querySelectorAll('[data-filter-form]');

                if (!priceFilterForms.length) {
                    return;
                }

                function asNumber(value, fallback) {
                    var number = parseFloat(value);

                    return Number.isFinite(number) ? number : fallback;
                }

                function formatNumber(value) {
                    if (!Number.isFinite(value)) {
                        return '0';
                    }

                    var fixed = value.toFixed(4);

                    return fixed.replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1');
                }

                function getWidgetParts(widget) {
                    return {
                        minRange: widget.querySelector('[data-price-display-min]'),
                        maxRange: widget.querySelector('[data-price-display-max]'),
                        minInput: widget.querySelector('[data-price-base-min-input]'),
                        maxInput: widget.querySelector('[data-price-base-max-input]'),
                        minLabel: widget.querySelector('[data-price-min-label]'),
                        maxLabel: widget.querySelector('[data-price-max-label]'),
                        progress: widget.querySelector('.progress-price')
                    };
                }

                function setPriceInputsEnabled(widget, enabled) {
                    var parts = getWidgetParts(widget);

                    [parts.minInput, parts.maxInput].forEach(function (input) {
                        if (!input) {
                            return;
                        }

                        if (enabled) {
                            input.removeAttribute('disabled');
                        } else {
                            input.setAttribute('disabled', 'disabled');
                        }
                    });

                    widget.dataset.priceFilterActive = enabled ? '1' : '0';
                }

                function syncPriceWidget(widget, activate, changedInput) {
                    var parts = getWidgetParts(widget);

                    if (!parts.minRange || !parts.maxRange || !parts.minInput || !parts.maxInput) {
                        return;
                    }

                    if (activate) {
                        setPriceInputsEnabled(widget, true);
                    }

                    var minLimit = asNumber(parts.minRange.min, 0);
                    var maxLimit = asNumber(parts.minRange.max, asNumber(parts.maxRange.max, minLimit + 1));
                    var minValue = asNumber(parts.minRange.value, minLimit);
                    var maxValue = asNumber(parts.maxRange.value, maxLimit);

                    if (minValue > maxValue) {
                        if (changedInput === parts.minRange) {
                            maxValue = minValue;
                            parts.maxRange.value = String(maxValue);
                        } else {
                            minValue = maxValue;
                            parts.minRange.value = String(minValue);
                        }
                    }

                    var rate = asNumber(widget.dataset.rate, 1);
                    if (rate <= 0) {
                        rate = 1;
                    }

                    parts.minInput.value = formatNumber(minValue * rate);
                    parts.maxInput.value = formatNumber(maxValue * rate);

                    if (parts.minLabel) {
                        parts.minLabel.textContent = formatNumber(minValue);
                    }

                    if (parts.maxLabel) {
                        parts.maxLabel.textContent = formatNumber(maxValue);
                    }

                    if (parts.progress) {
                        var denominator = Math.max(1, maxLimit - minLimit);
                        var left = ((minValue - minLimit) / denominator) * 100;
                        var right = 100 - (((maxValue - minLimit) / denominator) * 100);

                        parts.progress.style.left = Math.max(0, Math.min(100, left)) + '%';
                        parts.progress.style.right = Math.max(0, Math.min(100, right)) + '%';
                    }
                }

                document.addEventListener('input', function (event) {
                    var range = event.target.closest('[data-price-display-min], [data-price-display-max]');

                    if (!range) {
                        return;
                    }

                    var widget = range.closest('[data-price-filter-active]');
                    if (widget) {
                        syncPriceWidget(widget, true, range);
                    }
                }, true);

                document.addEventListener('change', function (event) {
                    var range = event.target.closest('[data-price-display-min], [data-price-display-max]');

                    if (!range) {
                        return;
                    }

                    var widget = range.closest('[data-price-filter-active]');
                    if (widget) {
                        syncPriceWidget(widget, true, range);
                    }
                }, true);

                document.addEventListener('submit', function (event) {
                    var form = event.target.closest('[data-filter-form]');

                    if (!form) {
                        return;
                    }

                    form.querySelectorAll('[data-price-filter-active]').forEach(function (widget) {
                        if (widget.dataset.priceFilterActive === '1') {
                            syncPriceWidget(widget, false, null);
                        } else {
                            setPriceInputsEnabled(widget, false);
                        }
                    });
                }, true);

                priceFilterForms.forEach(function (form) {
                    form.querySelectorAll('[data-price-filter-active]').forEach(function (widget) {
                        if (widget.dataset.priceFilterActive === '1') {
                            setPriceInputsEnabled(widget, true);
                            syncPriceWidget(widget, false, null);
                        } else {
                            setPriceInputsEnabled(widget, false);
                        }
                    });
                });
            })();
        </script>
    @endpush
@endonce
