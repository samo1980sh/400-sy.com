@php
    $categories = collect($filter_categories ?? []);
@endphp

<div class="offcanvas offcanvas-start canvas-filter" id="filterShop">
    <div class="canvas-wrapper">
        <header class="canvas-header">
            <div class="filter-icon">
                <span class="icon icon-filter"></span>
                <span>{{ app()->getLocale() === 'ar' ? 'فلتر' : 'Filter' }}</span>
            </div>
            <span class="icon-close icon-close-popup" data-bs-dismiss="offcanvas" aria-label="Close"></span>
        </header>
        <div class="canvas-body">
            <form action="{{ route('front.products.index') }}" id="facet-filter-form" class="facet-filter-form" method="get">
                <input type="hidden" name="sort" value="{{ $selected_sort ?? 'featured' }}">

                <div class="widget-facet wd-categories">
                    <div class="facet-title" data-bs-target="#categories" data-bs-toggle="collapse" aria-expanded="true" aria-controls="categories">
                        <span>{{ app()->getLocale() === 'ar' ? 'تصنيفات المنتجات' : 'Product categories' }}</span>
                        <span class="icon icon-arrow-up"></span>
                    </div>
                    <div id="categories" class="collapse show">
                        <ul class="list-categoris current-scrollbar mb_36">
                            <li class="cate-item {{ blank($selected_category_slug ?? null) ? 'current' : '' }}">
                                <label class="d-flex align-items-center gap-2">
                                    <input type="radio" name="category" value="" @checked(blank($selected_category_slug ?? null))>
                                    <span>{{ app()->getLocale() === 'ar' ? 'كل المنتجات' : 'All products' }}</span>
                                </label>
                            </li>
                            @foreach ($categories as $category)
                                @php
                                    $label = app()->getLocale() === 'ar'
                                        ? ($category->title_ar ?: $category->title_en ?: $category->slug)
                                        : ($category->title_en ?: $category->title_ar ?: $category->slug);
                                @endphp
                                <li class="cate-item {{ ($selected_category_slug ?? '') === $category->slug ? 'current' : '' }}">
                                    <label class="d-flex align-items-center gap-2">
                                        <input type="radio" name="category" value="{{ $category->slug }}" @checked(($selected_category_slug ?? '') === $category->slug)>
                                        <span>{{ $label }}</span>
                                    </label>
                                    @if ($category->children->isNotEmpty())
                                        <ul class="list-categoris ms-4 mt-2">
                                            @foreach ($category->children as $child)
                                                @php
                                                    $childLabel = app()->getLocale() === 'ar'
                                                        ? ($child->title_ar ?: $child->title_en ?: $child->slug)
                                                        : ($child->title_en ?: $child->title_ar ?: $child->slug);
                                                @endphp
                                                <li class="cate-item {{ ($selected_category_slug ?? '') === $child->slug ? 'current' : '' }}">
                                                    <label class="d-flex align-items-center gap-2">
                                                        <input type="radio" name="category" value="{{ $child->slug }}" @checked(($selected_category_slug ?? '') === $child->slug)>
                                                        <span>{{ $childLabel }}</span>
                                                    </label>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="widget-facet">
                    <div class="facet-title" data-bs-target="#price" data-bs-toggle="collapse" aria-expanded="true" aria-controls="price">
                        <span>{{ app()->getLocale() === 'ar' ? 'السعر' : 'Price' }}</span>
                        <span class="icon icon-arrow-up"></span>
                    </div>
                    <div id="price" class="collapse show">
                        <div class="widget-price filter-price">
                            <div class="box-title-price d-grid gap-3">
                                <div class="d-grid gap-2">
                                    <label class="title-price" for="min_price">{{ app()->getLocale() === 'ar' ? 'الحد الأدنى' : 'Min' }}</label>
                                    <input class="tf-input" id="min_price" type="number" name="min_price" min="0" step="1" value="{{ $selected_min_price ?? '' }}">
                                </div>
                                <div class="d-grid gap-2">
                                    <label class="title-price" for="max_price">{{ app()->getLocale() === 'ar' ? 'الحد الأعلى' : 'Max' }}</label>
                                    <input class="tf-input" id="max_price" type="number" name="max_price" min="0" step="1" value="{{ $selected_max_price ?? '' }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="tf-btn btn-fill animate-hover-btn flex-grow-1">
                        {{ app()->getLocale() === 'ar' ? 'تطبيق' : 'Apply' }}
                    </button>
                    <a href="{{ route('front.products.index') }}" class="tf-btn btn-outline animate-hover-btn flex-grow-1">
                        {{ app()->getLocale() === 'ar' ? 'إعادة ضبط' : 'Reset' }}
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
