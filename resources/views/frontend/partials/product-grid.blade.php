@php
    $items = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $products->getCollection()
        : collect($products ?? []);
@endphp

<div class="wrapper-control-shop" data-shop-results>
    @include('frontend.partials.shop-active-filters', [
        'active_filter_chips' => $active_filter_chips ?? [],
        'filter_reset_url' => $filter_reset_url ?? route('front.products.index'),
    ])
    <div class="grid-layout wrapper-shop loadmore-item" data-grid="grid-4" data-shop-grid>
        @forelse ($items as $product)
            @include('frontend.partials.product-card', [
                'product' => $product,
                'loadmore_hidden' => false,
            ])
        @empty
            <div class="col-12">
                <div class="empty-state py-5 text-center">
                    {{ app()->getLocale() === 'ar' ? 'لا توجد منتجات مطابقة.' : 'No matching products were found.' }}
                </div>
            </div>
        @endforelse
    </div>
</div>
