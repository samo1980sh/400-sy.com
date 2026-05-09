@php
    $sortOptions = collect($sort_options ?? []);
@endphp

<div class="tf-shop-control grid-3 align-items-center" data-shop-toolbar>
    <div class="tf-control-filter">
        <a href="#filterShop" data-bs-toggle="offcanvas" aria-controls="offcanvasLeft" class="tf-btn-filter">
            <span class="icon icon-filter"></span>
            <span class="text">{{ app()->getLocale() === 'ar' ? 'فلتر' : 'Filter' }}</span>
        </a>
    </div>

    <div class="tf-control-layout d-flex justify-content-center">
        <div class="text-center fw-6">
            {{ $result_count ?? 0 }} {{ app()->getLocale() === 'ar' ? 'منتج' : 'products' }}
        </div>
    </div>

    <div class="tf-control-sorting d-flex justify-content-end">
        <form action="{{ request()->url() }}" method="get" class="d-flex align-items-center gap-2" data-sort-form>
            @foreach (request()->except(['sort', 'page', 'filter_ajax', 'load_more']) as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $nestedValue)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $nestedValue }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <select name="sort" class="image-select tf-dropdown-sort" aria-label="{{ app()->getLocale() === 'ar' ? 'الفرز' : 'Sort' }}">
                @foreach ($sortOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($selected_sort ?? 'featured') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>
