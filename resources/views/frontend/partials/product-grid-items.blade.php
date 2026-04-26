@php
    $items = $products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator
        ? $products->getCollection()
        : collect($products ?? []);
@endphp

@forelse ($items as $product)
    @include('frontend.partials.product-card', [
        'product' => $product,
        'loadmore_hidden' => false,
    ])
@empty
@endforelse
