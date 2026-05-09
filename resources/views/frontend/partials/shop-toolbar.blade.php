@php
    $sortOptions = collect($sort_options ?? []);
    $selectedGrid = $selected_grid ?? 'grid-4';
    $gridLayouts = ['grid-2', 'grid-3', 'grid-4', 'grid-5', 'grid-6'];
    $selectedSort = $selected_sort ?? 'featured';
    $selectedSortLabel = $sortOptions[$selectedSort] ?? $sortOptions->first() ?? (app()->getLocale() === 'ar' ? 'مميز' : 'Featured');
@endphp

<div class="tf-shop-control grid-3 align-items-center" data-shop-toolbar>
    <div class="tf-control-filter">
        <a href="#filterShop" data-bs-toggle="offcanvas" aria-controls="offcanvasLeft" class="tf-btn-filter">
            <span class="icon icon-filter"></span>
            <span class="text">{{ app()->getLocale() === 'ar' ? 'فلتر' : 'Filter' }}</span>
        </a>
    </div>

    <div class="tf-control-layout d-flex justify-content-center">
        <ul class="tf-control-layout d-flex justify-content-center">
            @foreach ($gridLayouts as $gridLayout)
                @php
                    $gridNumber = str_replace('grid-', '', $gridLayout);
                @endphp
                <li
                    class="tf-view-layout-switch sw-layout-{{ $gridNumber }}{{ $selectedGrid === $gridLayout ? ' active' : '' }}"
                    data-value-grid="{{ $gridLayout }}"
                    onclick="document.querySelectorAll('.grid-layout').forEach(function(el){el.setAttribute('data-grid','{{ $gridLayout }}');});document.querySelectorAll('[data-grid-input]').forEach(function(el){el.value='{{ $gridLayout }}';});this.closest('.tf-control-layout').querySelectorAll('.tf-view-layout-switch.active').forEach(function(el){el.classList.remove('active');});this.classList.add('active');"
                >
                    <div class="item"><span class="icon icon-grid-{{ $gridNumber }}"></span></div>
                </li>
            @endforeach
        </ul>
    </div>

    <div class="tf-control-sorting d-flex justify-content-end">
        <form action="{{ request()->url() }}" method="get" class="d-flex align-items-center gap-2" data-sort-form>
            <input type="hidden" name="grid" value="{{ $selectedGrid }}" data-grid-input>
            @foreach (request()->except(['sort', 'page', 'filter_ajax', 'load_more']) as $key => $value)
                @if (is_array($value))
                    @foreach ($value as $nestedValue)
                        <input type="hidden" name="{{ $key }}[]" value="{{ $nestedValue }}">
                    @endforeach
                @else
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endif
            @endforeach
            <div class="tf-dropdown-sort" data-bs-toggle="dropdown">
                <div class="btn-select">
                    <span class="text-sort-value">{{ $selectedSortLabel }}</span>
                    <span class="icon icon-arrow-down"></span>
                </div>
                <div class="dropdown-menu">
                    @foreach ($sortOptions as $value => $label)
                        <div
                            class="select-item{{ $selectedSort === $value ? ' active' : '' }}"
                            onclick="var form=this.closest('[data-sort-form]');if(!form){return;}var select=form.querySelector('select[name=&quot;sort&quot;]');var text=form.querySelector('.text-sort-value');if(select){select.value='{{ $value }}';select.dispatchEvent(new Event('change',{bubbles:true}));}if(text){text.textContent='{{ addslashes($label) }}';}this.parentElement.querySelectorAll('.select-item.active').forEach(function(el){el.classList.remove('active');});this.classList.add('active');"
                        >
                            <span class="text-value-item">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
            <select name="sort" class="d-none" aria-label="{{ app()->getLocale() === 'ar' ? 'الفرز' : 'Sort' }}">
                @foreach ($sortOptions as $value => $label)
                    <option value="{{ $value }}" @selected($selectedSort === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>
