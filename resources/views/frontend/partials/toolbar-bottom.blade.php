<div class="tf-toolbar-bottom type-1150">
    <div class="toolbar-item">
        <a href="#canvasSearch" data-bs-toggle="offcanvas" aria-controls="offcanvasLeft">
            <div class="toolbar-icon"><i class="icon-search"></i></div>
            <div class="toolbar-label">{{ __('front.toolbar.search') }}</div>
        </a>
    </div>
    <div class="toolbar-item">
        <a href="#login" data-bs-toggle="modal">
            <div class="toolbar-icon"><i class="icon-account"></i></div>
            <div class="toolbar-label">{{ __('front.toolbar.account') }}</div>
        </a>
    </div>
    <div class="toolbar-item">
        <a href="javascript:void(0);">
            <div class="toolbar-icon"><i class="icon-heart"></i><div class="toolbar-count">0</div></div>
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
