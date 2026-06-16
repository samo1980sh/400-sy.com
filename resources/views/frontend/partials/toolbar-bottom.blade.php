<div class="tf-toolbar-bottom type-1150">
    <div class="toolbar-item">
        <a href="#canvasSearch" data-bs-toggle="offcanvas" aria-controls="offcanvasLeft">
            <div class="toolbar-icon"><i class="icon-search"></i></div>
            <div class="toolbar-label">{{ __('front.toolbar.search') }}</div>
        </a>
    </div>
    @php($toolbarCustomer = auth('customer')->user())
    <div class="toolbar-item">
        <a href="{{ $toolbarCustomer ? route('front.account.index') : '#login' }}" @unless($toolbarCustomer) data-bs-toggle="modal" @endunless>
            <div class="toolbar-icon"><i class="icon-account"></i></div>
            <div class="toolbar-label">{{ __('front.toolbar.account') }}</div>
        </a>
    </div>
    <div class="toolbar-item">
        <a href="{{ $wishlistUrl ?? ($wishlist_url ?? route('front.wishlist.index')) }}">
            <div class="toolbar-icon"><i class="icon-heart"></i><div class="toolbar-count" data-wishlist-count>{{ $wishlistCount ?? ($wishlist_count ?? 0) }}</div></div>
            <div class="toolbar-label">{{ __('front.toolbar.wishlist') }}</div>
        </a>
    </div>
    <div class="toolbar-item">
        <a href="#shoppingCart" data-bs-toggle="modal">
            <div class="toolbar-icon"><i class="icon-bag"></i><div class="toolbar-count" data-cart-count>{{ $cartCount ?? 0 }}</div></div>
            <div class="toolbar-label">{{ __('front.toolbar.cart') }}</div>
        </a>
    </div>
</div>
