@php
    $items = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $products->getCollection()
        : collect($products ?? []);
    $selectedGrid = $selected_grid ?? 'grid-4';
    $emptyTitle = trim((string) ($empty_state_title ?? ''));
    $emptyMessage = trim((string) ($empty_state_message ?? ''));
    $emptyResetUrl = trim((string) ($empty_state_reset_url ?? ''));
    $emptyResetLabel = trim((string) ($empty_state_reset_label ?? ''));
    $emptyAllUrl = trim((string) ($empty_state_all_url ?? ''));
    $emptyAllLabel = trim((string) ($empty_state_all_label ?? ''));
    $emptySearchTerm = trim((string) request()->query(
        'q',
        request()->query('text', request()->query('search', ''))
    ));
    $isSearchEmptyState = $items->isEmpty() && $emptySearchTerm !== '';
@endphp

<div class="wrapper-control-shop" data-shop-results>
    @unless ($isSearchEmptyState)
        @include('frontend.partials.shop-active-filters', [
            'active_filter_chips' => $active_filter_chips ?? [],
            'search_filter_chip' => $search_filter_chip ?? null,
            'category_context_chip' => $category_context_chip ?? null,
            'filter_reset_url' => $filter_reset_url ?? request()->url(),
        ])
    @endunless

    <div class="grid-layout wrapper-shop loadmore-item" data-grid="{{ $selectedGrid }}" data-shop-grid>
        @forelse ($items as $product)
            @include('frontend.partials.product-card', [
                'product' => $product,
                'loadmore_hidden' => false,
            ])
        @empty
            <div class="col-12" style="grid-column: 1 / -1;">
                @if ($isSearchEmptyState)
                    <div class="py-4 text-center">
                        <span class="icon icon-search d-inline-block mb-3" style="font-size: 22px;" aria-hidden="true"></span>

                        <h5 class="mb-2">
                            {{ $emptyTitle !== '' ? $emptyTitle : (app()->getLocale() === 'ar' ? 'لم نعثر على نتائج' : 'No results found') }}
                        </h5>

                        <p class="mb-3 text-muted">
                            {{ app()->getLocale() === 'ar'
                                ? 'لا توجد منتجات مطابقة لعبارة البحث «' . $emptySearchTerm . '».'
                                : 'No products match the search term “' . $emptySearchTerm . '”.' }}
                        </p>

                        @if ($emptyAllUrl !== '' && $emptyAllLabel !== '')
                            <a href="{{ $emptyAllUrl }}" class="tf-btn btn-fill justify-content-center fw-6 animate-hover-btn">
                                <span>{{ $emptyAllLabel }}</span>
                            </a>
                        @endif
                    </div>
                @else
                    <div class="empty-state py-5 text-center">
                        @if ($emptyTitle !== '')
                            <h4 class="mb-2">{{ $emptyTitle }}</h4>
                        @endif

                        <p class="mb-0 text-muted">
                            {{ $emptyMessage !== '' ? $emptyMessage : (app()->getLocale() === 'ar' ? 'لا توجد منتجات مطابقة.' : 'No matching products were found.') }}
                        </p>

                        @if ($emptyResetUrl !== '' || $emptyAllUrl !== '')
                            <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                                @if ($emptyResetUrl !== '' && $emptyResetLabel !== '')
                                    <a href="{{ $emptyResetUrl }}" class="tf-btn btn-fill justify-content-center fw-6 animate-hover-btn">
                                        <span>{{ $emptyResetLabel }}</span>
                                    </a>
                                @endif

                                @if ($emptyAllUrl !== '' && $emptyAllLabel !== '' && $emptyAllUrl !== $emptyResetUrl)
                                    <a href="{{ $emptyAllUrl }}" class="tf-btn style-2 btn-outline-dark radius-60 animate-hover-btn">
                                        <span>{{ $emptyAllLabel }}</span>
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endforelse
    </div>
</div>
