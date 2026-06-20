<aside class="account-sidebar">
    <div class="account-customer-head">
        <div class="d-flex align-items-center gap-3">
            <span class="account-avatar">{{ mb_strtoupper(mb_substr($customer->name ?: 'C', 0, 1)) }}</span>
            <div class="min-w-0">
                <div class="fw-7 text-truncate">{{ $customer->name }}</div>
                <small class="text-muted" dir="ltr">{{ $customer->account_no }}</small>
            </div>
        </div>
    </div>
    <nav class="account-nav">
        <a href="{{ route('front.account.index') }}" class="{{ request()->routeIs('front.account.index') ? 'active' : '' }}">
            <i class="icon icon-home"></i> {{ __('front.account.dashboard') }}
        </a>
        <a href="{{ route('front.account.profile') }}" class="{{ request()->routeIs('front.account.profile*') ? 'active' : '' }}">
            <i class="icon icon-account"></i> {{ __('front.account.profile') }}
        </a>
        <a href="{{ route('front.account.addresses') }}" class="{{ request()->routeIs('front.account.addresses*') ? 'active' : '' }}">
            <i class="icon icon-location"></i> {{ __('front.account.addresses') }}
        </a>
        <a href="{{ route('front.account.orders') }}" class="{{ request()->routeIs('front.account.orders*') ? 'active' : '' }}">
            <i class="icon icon-bag"></i> {{ __('front.account.orders') }}
        </a>
        
        <a href="{{ route('front.account.gift-card-requests.index') }}" class="{{ request()->routeIs('front.account.gift-card-requests*') ? 'active' : '' }}">
            <i class="icon icon-gift"></i> طلبات بطاقات الهدايا
        </a>
<a href="{{ route('front.wishlist.index') }}">
            <i class="icon icon-heart"></i> {{ __('front.toolbar.wishlist') }}
        </a>
        <form method="POST" action="{{ route('front.customer.logout') }}">
            @csrf
            <button type="submit"><i class="icon icon-close"></i> {{ __('front.account.logout') }}</button>
        </form>
    </nav>
</aside>
