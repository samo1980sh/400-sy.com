@php
    $items = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $products->getCollection()
        : collect($products ?? []);
    $selectedGrid = $selected_grid ?? 'grid-4';
@endphp

<div class="wrapper-control-shop" data-shop-results>
    @include('frontend.partials.shop-active-filters', [
        'active_filter_chips' => $active_filter_chips ?? [],
        'category_context_chip' => $category_context_chip ?? null,
        'filter_reset_url' => $filter_reset_url ?? request()->url(),
    ])
    <div class="grid-layout wrapper-shop loadmore-item" data-grid="{{ $selectedGrid }}" data-shop-grid>
        @forelse ($items as $product)
            @include('frontend.partials.product-card', [
                'product' => $product,
                'loadmore_hidden' => false,
            ])
        @empty
            <div class="col-12">
                <div class="empty-state py-5 text-center">
                    {{ ($empty_state_message ?? null) ?: (app()->getLocale() === 'ar' ? 'لا توجد منتجات مطابقة.' : 'No matching products were found.') }}
                </div>
            </div>
        @endforelse
    </div>
</div>
