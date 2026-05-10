@extends('frontend.layouts.app')

@php
    $locale = $locale ?? app()->getLocale();
    $isArabic = $locale === 'ar';
    $product = $product ?? [];
    $productModel = $product_model ?? null;
    $colors = collect($product['colors'] ?? [])->filter(fn ($color) => filled($color['name'] ?? null))->values();
    $defaultColor = $colors->first() ?? [];
    $gallery = collect($defaultColor['gallery'] ?? ($product['gallery'] ?? []))->filter()->values();
    $sizeOptions = collect($defaultColor['size_options'] ?? ($product['size_options'] ?? []))
        ->filter(fn ($size) => filled($size['size'] ?? ($size['name'] ?? ($size['label'] ?? null))))
        ->values();
    $defaultSize = $defaultColor['default_size'] ?? ($product['default_size'] ?? null);
    $description = trim((string) ($product['description'] ?? ''));
    $descriptionHtml = $description !== '' ? nl2br(e($description)) : '';
    $sizeChart = $product['size_chart'] ?? [];
    $hasSizeChart = !empty($product['has_size_chart']) && !empty($sizeChart['columns'] ?? []) && !empty($sizeChart['rows'] ?? []);
    $specifications = collect($product['specifications'] ?? [])->filter(fn ($item) => filled($item['label'] ?? null) && filled($item['value'] ?? null))->values();
    $relatedProducts = collect($related_products ?? [])->values();
    $categoryUrl = $productModel?->category?->slug ? route('front.category', $productModel->category->slug) : route('front.home');
    $relatedTitle = ($productModel?->relationLoaded('complements') && $productModel->complements->isNotEmpty())
        ? ($isArabic ? 'منتجات مكملة' : 'Complementary products')
        : ($isArabic ? 'قد يعجبك أيضاً' : 'You may also like');
@endphp

@section('title', $product['title'] ?? ($page_title ?? __('front.brand')))
@section('meta_description', $description !== '' ? $description : ($product['title'] ?? __('front.brand')))

@section('content')
    @include('frontend.partials.announcement-bar', ['tickerItems' => $ticker_items ?? [], 'socialLinks' => $social_links ?? []])
    @include('frontend.partials.header', [
        'navCategories' => $nav_categories ?? [],
        'currencyOptions' => $currency_options ?? [],
        'siteName' => $site_name ?? config('app.name', '400 Four HUNDRED'),
        'cartCount' => $cart_count ?? 0,
    ])

    <main>
        <div class="tf-breadcrumb">
            <div class="container">
                <div class="tf-breadcrumb-wrap d-flex justify-content-between flex-wrap align-items-center">
                    <div class="tf-breadcrumb-list">
                        @foreach (($breadcrumb_items ?? []) as $crumb)
                            @if (! $loop->first)
                                <i class="icon icon-arrow-right"></i>
                            @endif
                            @if ($loop->last)
                                <span class="text">{{ $crumb['label'] ?? '' }}</span>
                            @else
                                <a href="{{ $crumb['url'] ?? '#' }}" class="text">{{ $crumb['label'] ?? '' }}</a>
                            @endif
                        @endforeach
                    </div>
                    <div class="tf-breadcrumb-prev-next">
                        <a href="{{ $categoryUrl }}" class="tf-breadcrumb-back hover-tooltip center"><i class="icon icon-shop"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <section class="flat-spacing-4 pt_0">
            <div class="tf-main-product section-image-zoom" data-detail-product='@json($product)'>
                <div class="container">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="tf-product-media-wrap sticky-top">
                                <div class="thumbs-slider">
                                    <div dir="ltr" class="swiper tf-product-media-thumbs other-image-zoom" data-direction="vertical" data-detail-thumbs-swiper>
                                        <div class="swiper-wrapper stagger-wrap" data-detail-thumbs>
                                            @foreach ($gallery as $image)
                                                <div class="swiper-slide stagger-item" data-color="{{ $defaultColor['name'] ?? '' }}">
                                                    <div class="item"><img class="lazyload" data-src="{{ $image }}" src="{{ $image }}" alt="{{ $product['title'] ?? '' }}"></div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div dir="ltr" class="swiper tf-product-media-main" data-detail-main-swiper>
                                        <div class="swiper-wrapper" data-detail-gallery>
                                            @foreach ($gallery as $image)
                                                <div class="swiper-slide" data-color="{{ $defaultColor['name'] ?? '' }}">
                                                    <a href="{{ $image }}" target="_blank" class="item" data-pswp-width="770px" data-pswp-height="1075px">
                                                        <img class="tf-image-zoom lazyload" data-zoom="{{ $image }}" data-src="{{ $image }}" src="{{ $image }}" alt="{{ $product['title'] ?? '' }}">
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="swiper-button-next button-style-arrow single-slide-prev"></div>
                                        <div class="swiper-button-prev button-style-arrow single-slide-next"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="tf-product-info-wrap position-relative">
                                <div class="tf-zoom-main"></div>
                                <div class="tf-product-info-list other-image-zoom">
                                    <div class="tf-product-info-title"><h5>{{ $product['title'] ?? '' }}</h5></div>

                                    @if (!empty($product['badge']))
                                        <div class="tf-product-info-badges"><div class="badges {{ $product['badge_class'] ?? '' }}">{{ $product['badge'] }}</div></div>
                                    @endif

                                    <div class="tf-product-info-price">
                                        <div class="price-on-sale js-currency-price" data-detail-current-price data-base-price="{{ $product['price_current'] ?? $product['base_price'] ?? 0 }}" data-base-currency="{{ $product['base_currency'] ?? 'SYP' }}">
                                            {{ $product['price_current_label'] ?? $product['price_label'] ?? '' }}
                                        </div>
                                        <div class="compare-at-price js-currency-price {{ empty($product['compare_price_label']) ? 'd-none' : '' }}" data-detail-compare-price data-base-price="{{ $product['compare_price'] ?? 0 }}" data-base-currency="{{ $product['base_currency'] ?? 'SYP' }}">
                                            {{ $product['compare_price_label'] ?? '' }}
                                        </div>
                                    </div>

                                    @if (!empty($product['product_code']))
                                        <div class="tf-product-info-liveview"><p>{{ __('front.products.product_code') }}: <span class="fw-6">{{ $product['product_code'] }}</span></p></div>
                                    @endif
                                    @if (!empty($product['category_name']))
                                        <div class="tf-product-info-liveview"><p>{{ $isArabic ? 'القسم' : 'Category' }}: <a href="{{ $categoryUrl }}" class="fw-6 link">{{ $product['category_name'] }}</a></p></div>
                                    @endif
                                    @if (!empty($product['display_color_description']))
                                        <div class="tf-product-info-liveview"><p>{{ $isArabic ? 'لون المنتج المعروض' : 'Displayed color' }}: <span class="fw-6">{{ $product['display_color_description'] }}</span></p></div>
                                    @endif

                                    @if ($descriptionHtml !== '')
                                        <div class="tf-product-description"><p>{!! $descriptionHtml !!}</p></div>
                                    @endif

                                    <div class="tf-product-info-variant-picker">
                                        @if ($colors->isNotEmpty())
                                            <div class="variant-picker-item">
                                                <div class="variant-picker-label">{{ __('front.products.color') }}: <span class="fw-6 variant-picker-label-value" data-detail-color-label>{{ $defaultColor['name'] ?? '' }}</span></div>
                                                <div class="tf-product-info-code color-code"><span class="label">{{ __('front.products.color_code') }}:</span> <span class="value" data-detail-color-code>{{ $defaultColor['color_code'] ?? '' }}</span></div>
                                                <div class="variant-picker-values" data-detail-colors>
                                                    @foreach ($colors as $index => $color)
                                                        @php($swatchStyle = trim((string) ($color['swatch_style'] ?? '')))
                                                        <input id="detail-color-{{ $index }}" type="radio" name="detail_color" value="{{ $color['name'] }}" data-color-index="{{ $index }}" @checked($index === 0)>
                                                        <label class="hover-tooltip radius-60 color-btn" for="detail-color-{{ $index }}" data-value="{{ $color['name'] }}">
                                                            <span class="btn-checkbox {{ $color['class_name'] ?? 'four-Black' }}" style="{{ $swatchStyle !== '' ? $swatchStyle : '' }}"></span>
                                                            <span class="tooltip">{{ $color['name'] }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        <div class="variant-picker-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="variant-picker-label">{{ __('front.products.size') }}: <span class="fw-6 variant-picker-label-value" data-detail-size-label>{{ $defaultSize ?? '' }}</span></div>
                                                @if ($hasSizeChart)
                                                    <a href="#find_size" data-bs-toggle="modal" class="find-size fw-6" data-detail-find-size>{{ __('front.products.find_your_size') }}</a>
                                                @endif
                                            </div>
                                            <div class="variant-picker-values" data-detail-sizes>
                                                @foreach ($sizeOptions as $index => $size)
                                                    @php
                                                        $sizeValue = $size['size'] ?? ($size['name'] ?? ($size['label'] ?? ''));
                                                        $soldOut = !empty($size['is_sold_out']) || (($size['available'] ?? true) === false);
                                                    @endphp
                                                    @if ($sizeValue !== '')
                                                        <input type="radio" name="detail_size" id="detail-size-{{ $index }}" value="{{ $sizeValue }}" data-size-index="{{ $index }}" data-size-id="{{ $size['size_id'] ?? '' }}" data-size-code="{{ $size['size_code'] ?? '' }}" data-variant-id="{{ $size['variant_id'] ?? '' }}" data-product-color-id="{{ $size['product_color_id'] ?? '' }}" @checked($sizeValue === $defaultSize && ! $soldOut) @disabled($soldOut)>
                                                        <label class="style-text size-btn" for="detail-size-{{ $index }}" data-value="{{ $sizeValue }}" aria-disabled="{{ $soldOut ? 'true' : 'false' }}"><p>{{ $sizeValue }}</p></label>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <div class="tf-product-info-quantity">
                                        <div class="quantity-title fw-6">{{ __('front.products.quantity') }}</div>
                                        <div class="wg-quantity">
                                            <span class="btn-quantity btn-decrease" data-detail-qty="decrease">-</span>
                                            <input type="text" class="quantity-product" name="number" value="1" data-detail-quantity>
                                            <span class="btn-quantity btn-increase" data-detail-qty="increase">+</span>
                                        </div>
                                    </div>

                                    <div class="tf-product-info-buy-button">
                                        <form data-detail-cart-form data-cart-url="{{ $cartAddUrl }}">
                                            @csrf
                                            <button type="submit" class="tf-btn btn-fill justify-content-center fw-6 fs-16 flex-grow-1 animate-hover-btn btn-add-to-cart" data-detail-cart-submit @disabled(empty($cartAddUrl))>
                                                <span>{{ __('front.products.add_to_cart') }} -&nbsp;</span><span class="tf-qty-price total-price" data-detail-submit-price>{{ $product['price_current_label'] ?? $product['price_label'] ?? '' }}</span>
                                            </button>
                                            <a href="javascript:void(0);" class="tf-product-btn-wishlist hover-tooltip box-icon bg_white wishlist btn-icon-action">
                                                <span class="icon icon-heart"></span><span class="tooltip">{{ __('front.products.add_to_wishlist') }}</span><span class="icon icon-delete"></span>
                                            </a>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if ($descriptionHtml !== '' || $specifications->isNotEmpty() || $hasSizeChart)
            <section class="flat-spacing-17 pt_0">
                <div class="container">
                    <div class="row">
                        <div class="col-12">
                            <div class="widget-tabs style-has-border">
                                <ul class="widget-menu-tab">
                                    @if ($descriptionHtml !== '')
                                        <li class="item-title active"><span class="inner">{{ $isArabic ? 'وصف المنتج' : 'Description' }}</span></li>
                                    @endif
                                    @if ($specifications->isNotEmpty())
                                        <li class="item-title {{ $descriptionHtml === '' ? 'active' : '' }}"><span class="inner">{{ $isArabic ? 'المواصفات' : 'Additional Information' }}</span></li>
                                    @endif
                                    @if ($hasSizeChart)
                                        <li class="item-title {{ $descriptionHtml === '' && $specifications->isEmpty() ? 'active' : '' }}"><span class="inner">{{ __('front.products.size_chart') }}</span></li>
                                    @endif
                                </ul>
                                <div class="widget-content-tab">
                                    @if ($descriptionHtml !== '')
                                        <div class="widget-content-inner active"><div class="tab-description"><p>{!! $descriptionHtml !!}</p></div></div>
                                    @endif
                                    @if ($specifications->isNotEmpty())
                                        <div class="widget-content-inner {{ $descriptionHtml === '' ? 'active' : '' }}">
                                            <div class="tf-page-privacy-policy">
                                                @foreach ($specifications as $spec)
                                                    <div class="d-flex justify-content-between flex-wrap gap-3 py-3 border-bottom">
                                                        <div class="fw-6">{{ $spec['label'] }}</div>
                                                        <div>{{ $spec['value'] }}</div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if ($hasSizeChart)
                                        <div class="widget-content-inner {{ $descriptionHtml === '' && $specifications->isEmpty() ? 'active' : '' }}">
                                            <div class="tab-description"><a href="#find_size" data-bs-toggle="modal" class="tf-btn btn-line" data-detail-find-size>{{ __('front.products.size_chart') }}<i class="icon icon-arrow1-top-left"></i></a></div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if ($relatedProducts->isNotEmpty())
            <section class="flat-spacing-1 pt_0">
                <div class="container">
                    <div class="flat-title"><span class="title">{{ $relatedTitle }}</span></div>
                    <div class="grid-layout wrapper-shop" data-grid="grid-4">
                        @foreach ($relatedProducts as $relatedProduct)
                            @include('frontend.partials.product-card', ['product' => $relatedProduct, 'loadmore_hidden' => false])
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </main>

    @include('frontend.partials.footer', ['contact' => $contact ?? null, 'socialLinks' => $social_links ?? [], 'footerPages' => $footer_pages ?? [], 'collections' => $collections ?? []])
    @include('frontend.partials.toolbar-bottom', ['cartCount' => $cart_count ?? 0])
    @include('frontend.partials.mobile-menu', ['navCategories' => $nav_categories ?? [], 'quickLinks' => $quick_links ?? []])
    @include('frontend.partials.search-canvas', ['quickLinks' => $quick_links ?? []])
    @include('frontend.partials.shopping-cart', ['cartState' => $cart_state ?? []])
    @include('frontend.partials.auth-modals')
    @include('frontend.partials.quick-add')
    @include('frontend.partials.quick-view')
    @include('frontend.partials.find-size')
@endsection

@push('scripts')
    @include('frontend.partials.product-scripts')
    <script>
        (function($){var $r=$('[data-detail-product]').first(),p=$r.data('detail-product')||{},c=Array.isArray(p.colors)?p.colors:[],ci=0,si=0,t=null,m=null;if(!$r.length)return;function e(v){return String(v||'').replace(/[&<>"']/g,function(ch){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[ch];});}function col(){return c[ci]||c[0]||{};}function sizes(){var x=col();return Array.isArray(x.size_options)&&x.size_options.length?x.size_options:(Array.isArray(p.size_options)?p.size_options:[]);}function sold(s){return s&&(s.is_sold_out===true||s.available===false||Number(s.quantity||0)<=0);}function init(){if(typeof Swiper==='undefined')return;if(t&&t.destroy)t.destroy(true,true);if(m&&m.destroy)m.destroy(true,true);var te=document.querySelector('[data-detail-thumbs-swiper]'),me=document.querySelector('[data-detail-main-swiper]');if(!te||!me)return;t=new Swiper(te,{direction:'vertical',slidesPerView:5,spaceBetween:12,watchSlidesProgress:true});m=new Swiper(me,{slidesPerView:1,spaceBetween:0,thumbs:{swiper:t},navigation:{nextEl:me.querySelector('.swiper-button-next'),prevEl:me.querySelector('.swiper-button-prev')}});}function gallery(x){var g=Array.isArray(x.gallery)&&x.gallery.length?x.gallery:(x.image?[x.image]:(Array.isArray(p.gallery)?p.gallery:[]));g=g.filter(Boolean);$('[data-detail-thumbs]').html(g.map(function(i){return '<div class="swiper-slide stagger-item" data-color="'+e(x.name||'')+'"><div class="item"><img class="lazyload" data-src="'+e(i)+'" src="'+e(i)+'" alt="'+e(p.title||'')+'"></div></div>';}).join(''));$('[data-detail-gallery]').html(g.map(function(i){return '<div class="swiper-slide" data-color="'+e(x.name||'')+'"><a href="'+e(i)+'" target="_blank" class="item" data-pswp-width="770px" data-pswp-height="1075px"><img class="tf-image-zoom lazyload" data-zoom="'+e(i)+'" data-src="'+e(i)+'" src="'+e(i)+'" alt="'+e(p.title||'')+'"></a></div>';}).join(''));init();}function renderSizes(){var a=sizes(),f=a.findIndex(function(s){return !sold(s);});if(f<0)f=0;si=f;$('[data-detail-sizes]').html(a.map(function(s,i){var v=s.size||s.name||s.label||'',d=sold(s)?' disabled':'',ck=i===f&&!sold(s)?' checked':'';if(!v)return '';return '<input type="radio" name="detail_size" id="detail-size-js-'+i+'" value="'+e(v)+'" data-size-index="'+i+'" data-size-id="'+e(s.size_id||'')+'" data-size-code="'+e(s.size_code||'')+'" data-variant-id="'+e(s.variant_id||'')+'" data-product-color-id="'+e(s.product_color_id||'')+'"'+ck+d+'><label class="style-text size-btn" for="detail-size-js-'+i+'" data-value="'+e(v)+'"'+(sold(s)?' aria-disabled="true"':'')+'><p>'+e(v)+'</p></label>';}).join(''));}function price(){var x=col(),a=sizes(),s=a[si]||null,q=Math.max(1,Math.min(99,parseInt($('[data-detail-quantity]').val(),10)||1)),cu=p.base_currency||'SYP',cl=(s&&s.price_current_label)||x.price_current_label||p.price_current_label||p.price_label||'',xl=(s&&s.compare_price_label)||x.compare_price_label||p.compare_price_label||'',cb=(s&&s.price_current)||x.price_current||p.price_current||p.base_price||0,xb=(s&&s.compare_price)||x.compare_price||p.compare_price||0,tl=(Math.round(Number(cb||0)*q)).toLocaleString()+' '+cu;$('[data-detail-current-price]').text(cl).attr({'data-base-price':cb||0,'data-base-currency':cu});$('[data-detail-submit-price]').text(tl);$('[data-detail-compare-price]').text(xl||'').attr({'data-base-price':xb||0,'data-base-currency':cu}).toggleClass('d-none',!xl);$('[data-detail-color-label]').text(x.name||'');$('[data-detail-color-code]').text(x.color_code||'');$('[data-detail-size-label]').text(s?(s.size||s.name||s.label||''):'');if(window.updateCurrencyConvertedPrices)window.updateCurrencyConvertedPrices();}function chart(){var $m=$('#find_size'),ch=p.size_chart||{},r=Array.isArray(ch.rows)?ch.rows:[],cs=Array.isArray(ch.columns)?ch.columns:[],$t=$m.find('[data-size-chart-table]'),$h=$m.find('[data-size-chart-head]'),$b=$m.find('[data-size-chart-body]'),$e=$m.find('[data-size-chart-empty]'),$gw=$m.find('[data-size-chart-guide-wrap]'),$gi=$m.find('[data-size-chart-guide-image]'),$tw=$m.find('[data-size-chart-table-wrap]'),img=String(ch.guide_image||'').trim();$m.find('[data-size-chart-title]').text(ch.title||'');$m.find('[data-size-chart-subtitle]').text(ch.subtitle||'');if(img){$gi.attr('src',img);$gw.removeClass('d-none');$tw.removeClass('col-lg-12').addClass('col-lg-8');}else{$gi.attr('src','');$gw.addClass('d-none');$tw.removeClass('col-lg-8').addClass('col-lg-12');}if(!r.length||!cs.length){$t.addClass('d-none');$e.removeClass('d-none');$h.empty();$b.empty();return;}$h.html(cs.map(function(c){return '<th>'+e(c.label||'')+'</th>';}).join(''));$b.html(r.map(function(row){return '<tr>'+cs.map(function(c){var v=row[c.key]??'';return '<td>'+e(v===null||v===undefined||v===''?'-':String(v))+'</td>';}).join('')+'</tr>';}).join(''));$e.addClass('d-none');$t.removeClass('d-none');}function cartState(res){var $f=$('<div>').html(res.cart_html||''),$n=$f.find('#shoppingCart'),count=(res.cart_state&&res.cart_state.count)||0;$('[data-cart-count]').text(count);if($n.length&&$('#shoppingCart').length){var $m=$('#shoppingCart'),$ns=$n.find('[data-cart-subtotal]'),$s=$m.find('[data-cart-subtotal]');$m.find('[data-cart-items]').html($n.find('[data-cart-items]').html());if($s.length&&$ns.length){$s.text($ns.text()).attr({'data-base-price':$ns.attr('data-base-price')||0,'data-base-currency':$ns.attr('data-base-currency')||($('.js-currency-select').val()||'')});}if(window.updateCurrencyConvertedPrices)window.updateCurrencyConvertedPrices();}}$(document).on('change','[data-detail-colors] input[name=\"detail_color\"]',function(){ci=Number($(this).data('color-index')||0);gallery(col());renderSizes();price();});$(document).on('change','[data-detail-sizes] input[name=\"detail_size\"]',function(){si=Number($(this).data('size-index')||0);price();});$(document).on('click','[data-detail-qty]',function(){var $q=$('[data-detail-quantity]'),n=parseInt($q.val(),10)||1;$q.val($(this).data('detail-qty')==='decrease'?Math.max(1,n-1):Math.min(99,n+1));price();});$(document).on('click','[data-detail-find-size]',function(ev){ev.preventDefault();chart();$('#find_size').modal('show');});$('[data-detail-cart-form]').on('submit',function(ev){ev.preventDefault();var url=String($(this).data('cart-url')||''),x=col(),$s=$('[data-detail-sizes] input[name=\"detail_size\"]:checked').first(),q=Math.max(1,Math.min(99,parseInt($('[data-detail-quantity]').val(),10)||1)),d={quantity:q,color:x.name||'',color_name:x.name||'',color_id:x.id||'',color_code:x.color_code||''};if(!url)return;if($s.length){d.size=$s.val()||'';d.size_id=$s.data('size-id')||'';d.size_code=$s.data('size-code')||'';d.variant_id=$s.data('variant-id')||'';}$('[data-detail-cart-submit]').prop('disabled',true);$.ajax({url:url,type:'POST',data:d,dataType:'json',headers:{'X-CSRF-TOKEN':$('meta[name=\"csrf-token\"]').attr('content')||'',Accept:'application/json'}}).done(function(res){cartState(res||{});$('#shoppingCart').modal('show');}).fail(function(xhr){console.error('Detail add-to-cart failed',xhr);}).always(function(){$('[data-detail-cart-submit]').prop('disabled',false);});});chart();gallery(col());renderSizes();price();})(jQuery);
    </script>
@endpush
