@php
    $contact = $contact ?? null;
    $footerPages = collect($footerPages ?? []);
    $collections = collect($collections ?? []);

    $contactName = $contact
        ? (app()->getLocale() === 'ar'
            ? ($contact->company_name_ar ?? $contact->company_name_en ?? __('front.brand'))
            : ($contact->company_name_en ?? $contact->company_name_ar ?? __('front.brand')))
        : __('front.brand');

    $address = $contact
        ? (app()->getLocale() === 'ar'
            ? ($contact->address_ar ?? $contact->address_en ?? __('front.footer.address'))
            : ($contact->address_en ?? $contact->address_ar ?? __('front.footer.address')))
        : __('front.footer.address');

    $phone = $contact->phone ?? $contact->mobile ?? '+963 11 691 2400';
    $email = $contact->email ?? 'info.sy@400-online.com';

    $importantFooterLinks = collect([
        [
            'title' => __('front.cart.terms_and_conditions'),
            'url' => route('front.pages.show', 'terms-and-conditions'),
        ],
        [
            'title' => app()->getLocale() === 'ar' ? 'سياسة الاستبدال والإرجاع' : 'Exchange and Return Policy',
            'url' => route('front.pages.show', 'exchange-and-return-policy'),
        ],
        [
            'title' => app()->getLocale() === 'ar' ? 'الأسئلة الشائعة' : 'Frequently Asked Questions',
            'url' => route('front.pages.show', 'faq'),
        ],
    ]);

    $existingFooterUrls = $footerPages
        ->pluck('url')
        ->filter()
        ->map(fn ($url) => rtrim((string) $url, '/'));

    $importantFooterLinks = $importantFooterLinks
        ->reject(fn (array $link) => $existingFooterUrls->contains(rtrim((string) $link['url'], '/')))
        ->values();

@endphp

<footer id="footer" class="footer background-black md-pb-70">
    <div class="footer-wrap">
        <div class="footer-body">
            <div class="container">
                <div class="row">
                    <div class="col-xl-3 col-md-6 col-12">
                        <div class="footer-infor">
                            <div class="footer-logo">
                                <a href="{{ route('front.home') }}">
                                    <img src="{{ asset('images/logo/logo2.png') }}" alt="{{ $contactName }}">
                                </a>
                            </div>
                            <ul>
                                <li><p>{{ __('front.footer.address_label') }} {{ $address }}</p></li>
                                <li><p>{{ __('front.footer.email_label') }} <a href="mailto:{{ $email }}">{{ $email }}</a></p></li>
                                <li><p>{{ __('front.footer.phone_label') }} <a href="tel:{{ $phone }}" dir="ltr"><span dir="ltr">{{ $phone }}</span></a></p></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-xl-2 col-md-6 col-12 footer-col-block">
                        <div class="footer-heading footer-heading-desktop">
                            <h6>{{ __('front.footer.products') }}</h6>
                        </div>
                        <div class="footer-heading footer-heading-moblie">
                            <h6>{{ __('front.footer.products') }}</h6>
                        </div>
                        <ul class="footer-menu-list tf-collapse-content">
                            @foreach ($collections->take(6) as $collection)
                                <li><a href="{{ $collection['link'] ?? '#' }}" class="footer-menu_item">{{ $collection['title'] ?? '' }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="col-xl-2 col-md-6 col-12 footer-col-block">
                        <div class="footer-heading footer-heading-desktop">
                            <h6>{{ __('front.footer.links') }}</h6>
                        </div>
                        <div class="footer-heading footer-heading-moblie">
                            <h6>{{ __('front.footer.links') }}</h6>
                        </div>
                        <ul class="footer-menu-list tf-collapse-content">
                            @foreach ($footerPages as $page)
                                <li><a href="{{ $page['url'] ?? '#' }}" class="footer-menu_item">{{ $page['title'] ?? '' }}</a></li>
                            @endforeach
                            @foreach ($importantFooterLinks as $link)
                                <li><a href="{{ $link['url'] }}" class="footer-menu_item">{{ $link['title'] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="col-xl-2 col-md-6 col-12 footer-col-block">
                        <div class="footer-heading footer-heading-desktop">
                            <h6>{{ __('front.footer.follow') }}</h6>
                        </div>
                        <div class="footer-heading footer-heading-moblie">
                            <h6>{{ __('front.footer.follow') }}</h6>
                        </div>
                        <ul class="footer-menu-list tf-collapse-content">
                            <li><a href="#" class="footer-menu_item">{{ __('front.footer.employment') }}</a></li>
                            <li><a href="#" class="footer-menu_item">{{ __('front.footer.contracting') }}</a></li>
                            <li><a href="#" class="footer-menu_item">{{ __('front.footer.loyalty_card') }}</a></li>
                            <li><a href="#" class="footer-menu_item">{{ __('front.footer.language_link') }}</a></li>
                        </ul>
                    </div>
                    <div class="col-xl-3 col-md-6 col-12">
                        <div class="footer-newsletter footer-col-block">
                            <div class="footer-heading footer-heading-desktop">
                                <h6>{{ __('front.footer.newsletter') }}</h6>
                            </div>
                            <div class="footer-heading footer-heading-moblie">
                                <h6>{{ __('front.footer.newsletter') }}</h6>
                            </div>
                            <div class="tf-collapse-content">
                                <div class="footer-menu_item">{{ __('front.footer.newsletter_text') }}</div>
                                <form class="form-newsletter" id="subscribe-form" action="#" method="post" accept-charset="utf-8" data-mailchimp="true">
                                    <div id="subscribe-content">
                                        <fieldset class="email">
                                            <input type="email" name="email-form" id="subscribe-email" placeholder="{{ __('front.footer.email_placeholder') }}" tabindex="0" aria-required="true">
                                        </fieldset>
                                        <div class="button-submit">
                                            <button id="subscribe-button" class="tf-btn btn-sm radius-3 btn-fill btn-icon animate-hover-btn" type="button">{{ __('front.footer.subscribe') }}<i class="icon icon-arrow1-top-left"></i></button>
                                        </div>
                                    </div>
                                    <div id="subscribe-msg"></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <div class="footer-bottom-wrap d-flex gap-20 flex-wrap justify-content-between align-items-center">
                            <div class="footer-menu_item" dir="ltr">© 2026 400 Four HUNDRED</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
