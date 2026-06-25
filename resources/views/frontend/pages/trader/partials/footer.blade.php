@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $brandName = $siteName ?? $site_name ?? __('front.brand');
@endphp

<footer class="trader-shell-footer" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
    <div class="container">
        <div class="trader-shell-footer__inner">
            <span><strong>{{ $brandName }}</strong> {{ $isArabic ? 'بوابة تجار الجملة' : 'Wholesale Trader Portal' }}</span>
            <span dir="ltr">&copy; {{ date('Y') }}</span>
        </div>
    </div>
</footer>
