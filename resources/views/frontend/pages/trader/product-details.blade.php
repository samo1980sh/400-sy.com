@extends('frontend.layouts.app')

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $presentation = $product_presentation ?? [];

    $title = (string) ($presentation['title'] ?? ($isArabic
        ? ($product->title_ar ?: $product->title_en ?: $product->model_no)
        : ($product->title_en ?: $product->title_ar ?: $product->model_no)));

    $groupName = $wholesale_group
        ? ($isArabic
            ? ($wholesale_group->name_ar ?? $wholesale_group->name_en ?? $wholesale_group->name ?? '#'.$wholesale_group->id)
            : ($wholesale_group->name_en ?? $wholesale_group->name_ar ?? $wholesale_group->name ?? '#'.$wholesale_group->id))
        : '-';

    $categoryTitle = (string) ($presentation['category_name'] ?? '');
    if ($categoryTitle === '' && $product->category) {
        $categoryTitle = $isArabic
            ? ($product->category->name_ar ?? $product->category->title_ar ?? $product->category->name ?? '')
            : ($product->category->name_en ?? $product->category->title_en ?? $product->category->name ?? '');
    }

    $availableColors = $product->wholesaleAvailabilities
        ->filter(fn ($availability) => (int) $availability->max_quantity > 0)
        ->values();

    $colorsById = $product->wholesaleColors->keyBy('id');
    $quantityRows = $product->wholesaleQuantities ?? collect();
    $currency = (string) ($presentation['base_currency'] ?? ($isArabic ? ($product->currency_ar ?: 'ل.س') : ($product->currency_en ?: 'SYP')));
    $unitPrice = (float) (
        $presentation['base_price']
        ?? $presentation['price_current']
        ?? $presentation['compare_price']
        ?? $product->price
        ?? $product->compare_price
        ?? 0
    );
    $priceText = (string) (
        $presentation['base_price_label']
        ?? $presentation['price_label']
        ?? $presentation['compare_price_label']
        ?? ($unitPrice > 0 ? number_format($unitPrice, 0).' '.$currency : ($isArabic ? 'السعر غير مضبوط' : 'Price not configured'))
    );
    $mainImageUrl = (string) ($presentation['image'] ?? '');
@endphp

@section('title', $page_title ?? $title)
@section('meta_description', $page_subtitle ?? __('front.brand'))

@push('styles')
    <style>
        .trader-product { padding: 56px 0 72px; background: #fff; font-family: "Albert Sans", sans-serif; }
        .trader-product__toolbar { display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 22px; }
        .trader-btn { min-height: 42px; display: inline-flex; align-items: center; justify-content: center; padding: 8px 18px; border-radius: 3px; border: 1px solid #d8d8d8; background: #fff; color: #111; font-weight: 800; }
        .trader-btn:hover { color: #111; border-color: #111; background: #f7f7f7; }
        .trader-btn--dark { background: #111; border-color: #111; color: #fff; }
        .trader-btn--dark:hover { background: #333; border-color: #333; color: #fff; }
        .trader-alert { margin-bottom: 18px; border-radius: 3px; padding: 13px 16px; line-height: 1.8; font-weight: 700; }
        .trader-alert--success { border: 1px solid rgba(25,135,84,.25); background: rgba(25,135,84,.08); color: #0f5132; }
        .trader-alert--error { border: 1px solid rgba(220,53,69,.25); background: rgba(220,53,69,.08); color: #842029; }
        .trader-product__shell { display: grid; grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr); gap: 26px; align-items: start; }
        .trader-product__media, .trader-product__info, .trader-product__section { border: 1px solid #e7e7e7; border-radius: 8px; background: #fff; }
        .trader-product__media { min-height: 520px; display: flex; align-items: center; justify-content: center; padding: 24px; position: sticky; top: 24px; overflow: hidden; }
        .trader-product__media img { width: 100%; max-height: 620px; object-fit: contain; display: block; background: #fff; }
        .trader-product__placeholder { width: 100%; min-height: 440px; background: #f6f6f6; display: flex; align-items: center; justify-content: center; text-align: center; padding: 24px; color: #666; font-weight: 800; }
        .trader-product__info { padding: 26px; }
        .trader-product__badge { display: inline-flex; min-height: 30px; align-items: center; justify-content: center; padding: 4px 12px; border-radius: 999px; background: #111; color: #fff; font-size: 13px; font-weight: 800; margin-bottom: 16px; }
        .trader-product__info h1 { margin: 0 0 14px; color: #111; font-size: clamp(28px, 4vw, 44px); line-height: 1.28; font-weight: 600; }
        .trader-product__price { margin: 0 0 18px; padding: 14px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .trader-product__price span { color: #777; font-weight: 800; }
        .trader-product__price strong { color: #111; font-size: 22px; font-weight: 900; }
        .trader-product__meta { display: grid; gap: 0; margin: 20px 0; }
        .trader-product__meta-row { display: flex; align-items: center; justify-content: space-between; gap: 14px; padding: 12px 0; border-bottom: 1px solid #eee; }
        .trader-product__meta-row span { color: #777; }
        .trader-product__meta-row strong { color: #111; text-align: end; }
        .trader-product__colors { display: grid; gap: 14px; margin-top: 22px; }
        .trader-color { border: 1px solid #e7e7e7; border-radius: 8px; background: #fff; padding: 16px; }
        .trader-color__head { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
        .trader-color__name { color: #111; font-size: 16px; font-weight: 800; }
        .trader-pill { display: inline-flex; align-items: center; justify-content: center; min-height: 28px; padding: 4px 10px; border-radius: 999px; background: #f5f5f5; color: #111; font-size: 12px; font-weight: 800; white-space: nowrap; }
        .trader-series-title { margin: 14px 0 8px; color: #555; font-size: 13px; font-weight: 800; }
        .trader-matrix { width: 100%; border: 1px solid #ddd; overflow-x: auto; background: #fff; margin-bottom: 10px; direction: ltr; }
        .trader-matrix__row { display: grid; grid-auto-flow: column; grid-auto-columns: minmax(42px, 1fr); align-items: center; min-height: 36px; }
        .trader-matrix__row + .trader-matrix__row { border-top: 1px solid #e5e5e5; }
        .trader-matrix__cell { display: flex; align-items: center; justify-content: center; min-height: 36px; padding: 5px 7px; color: #111; font-size: 14px; font-weight: 900; border-right: 1px solid #ededed; }
        .trader-matrix__cell:last-child { border-right: 0; }
        .trader-series-order { margin: 10px 0 18px; padding: 14px; border: 1px solid #eee; background: #fafafa; }
        .trader-series-order__row { display: grid; grid-template-columns: minmax(120px, 1fr) 110px auto; gap: 10px; align-items: end; }
        .trader-series-order label { display: block; color: #666; font-size: 13px; font-weight: 800; margin-bottom: 7px; }
        .trader-series-order input { width: 100%; min-height: 42px; border-radius: 3px; border: 1px solid #ddd; padding: 6px 12px; color: #111; background: #fff; font-weight: 800; }
        .trader-series-order button { min-height: 42px; border: 0; border-radius: 3px; background: #111; color: #fff; font-weight: 900; padding: 8px 18px; white-space: nowrap; }
        .trader-series-order__help { margin-top: 8px; color: #777; font-size: 12px; line-height: 1.7; }
        .trader-series-order__summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin-top: 12px; }
        .trader-series-order__summary-item { border: 1px solid #eee; background: #fff; padding: 9px 10px; border-radius: 3px; }
        .trader-series-order__summary-item span { display: block; color: #777; font-size: 11px; font-weight: 800; margin-bottom: 4px; }
        .trader-series-order__summary-item strong { color: #111; font-size: 13px; font-weight: 900; }
        .trader-empty { border: 1px dashed #d8d8d8; border-radius: 8px; padding: 14px; color: #777; line-height: 1.7; background: #fff; }
        .trader-product__section { margin-top: 24px; padding: 24px; }
        .trader-product__section h2 { margin: 0 0 12px; color: #111; font-size: 24px; font-weight: 700; }
        .trader-product__section p { margin: 0; color: #666; line-height: 1.9; }
        @media (max-width: 991px) {
            .trader-product__toolbar { align-items: stretch; flex-direction: column; }
            .trader-product__toolbar .trader-btn { width: fit-content; }
            .trader-product__shell { grid-template-columns: 1fr; }
            .trader-product__media { position: relative; top: auto; min-height: auto; }
        }
        @media (max-width: 575px) {
            .trader-product { padding: 42px 0 58px; }
            .trader-product__info, .trader-product__section, .trader-product__media { padding: 18px; }
            .trader-color__head, .trader-product__meta-row, .trader-product__price { align-items: stretch; flex-direction: column; }
            .trader-series-order__row { grid-template-columns: 1fr; }
            .trader-series-order__summary { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content')
    @include('frontend.pages.trader.partials.header', ['traderCartCount' => $trader_cart_count ?? 0])

    <main>
        @include('frontend.partials.page-title', [
            'title' => $page_title ?? $title,
            'subtitle' => $page_subtitle ?? '',
            'breadcrumbs' => $breadcrumb_items ?? [],
            'background' => $page_title_background ?? null,
        ])

        <section class="trader-product">
            <div class="container">
                <div class="trader-product__toolbar">
                    <a href="{{ route('front.trader.products.index') }}" class="trader-btn">{{ $isArabic ? 'العودة لمنتجات الجملة' : 'Back to Wholesale Products' }}</a>
                    <a href="{{ route('front.trader.cart.index') }}" class="trader-btn trader-btn--dark">{{ $isArabic ? 'عرض طلب الجملة المؤقت' : 'View Wholesale Cart' }}</a>
                </div>

                @if (session('success'))
                    <div class="trader-alert trader-alert--success">{{ session('success') }}</div>
                @endif

                @if ($errors->any())
                    <div class="trader-alert trader-alert--error">{{ $errors->first() }}</div>
                @endif

                <div class="trader-product__shell">
                    <div class="trader-product__media">
                        @if ($mainImageUrl !== '')
                            <img src="{{ $mainImageUrl }}" alt="{{ $title }}">
                        @else
                            <div class="trader-product__placeholder">{{ $title }}</div>
                        @endif
                    </div>

                    <div class="trader-product__info">
                        <span class="trader-product__badge">{{ $isArabic ? 'جملة' : 'Wholesale' }}</span>
                        <h1>{{ $title }}</h1>

                        <div class="trader-product__price">
                            <span>{{ $isArabic ? 'سعر القطعة' : 'Unit Price' }}</span>
                            <strong dir="ltr">{{ $priceText }}</strong>
                        </div>

                        <div class="trader-product__meta">
                            <div class="trader-product__meta-row">
                                <span>{{ $isArabic ? 'رمز المنتج' : 'Product Code' }}</span>
                                <strong dir="ltr">{{ $product->model_no }}</strong>
                            </div>
                            @if ($categoryTitle !== '')
                                <div class="trader-product__meta-row">
                                    <span>{{ $isArabic ? 'التصنيف' : 'Category' }}</span>
                                    <strong>{{ $categoryTitle }}</strong>
                                </div>
                            @endif
                            <div class="trader-product__meta-row">
                                <span>{{ $isArabic ? 'مجموعة الجملة' : 'Wholesale Group' }}</span>
                                <strong>{{ $groupName }}</strong>
                            </div>
                            <div class="trader-product__meta-row">
                                <span>{{ $isArabic ? 'الألوان المتاحة لحسابك' : 'Colors Available for You' }}</span>
                                <strong dir="ltr">{{ $availableColors->count() }}</strong>
                            </div>
                        </div>

                        <div class="trader-product__colors">
                            @forelse ($availableColors as $availability)
                                @php
                                    $color = $colorsById->get($availability->product_wholesale_color_id);
                                    $colorName = $color
                                        ? ($isArabic
                                            ? ($color->color_name_ar ?? $color->color_name_en ?? $color->color_code ?? '')
                                            : ($color->color_name_en ?? $color->color_name_ar ?? $color->color_code ?? ''))
                                        : '';
                                    $maxSeries = (int) $availability->max_quantity;
                                    $colorQuantities = $quantityRows
                                        ->filter(fn ($row) => (int) $row->product_wholesale_color_id === (int) $availability->product_wholesale_color_id && (int) $row->quantity > 0)
                                        ->sortBy(fn ($row) => str_pad((string) ((int) $row->series_group), 4, '0', STR_PAD_LEFT).'|'.str_pad((string) $row->size_text, 8, '0', STR_PAD_LEFT))
                                        ->values();

                                    if ($colorQuantities->isEmpty()) {
                                        $colorQuantities = $quantityRows
                                            ->filter(fn ($row) => blank($row->product_wholesale_color_id) && (int) $row->quantity > 0)
                                            ->sortBy(fn ($row) => str_pad((string) ((int) $row->series_group), 4, '0', STR_PAD_LEFT).'|'.str_pad((string) $row->size_text, 8, '0', STR_PAD_LEFT))
                                            ->values();
                                    }

                                    $seriesGroups = $colorQuantities->groupBy(fn ($row) => (int) $row->series_group)->sortKeys();
                                @endphp

                                <div class="trader-color">
                                    <div class="trader-color__head">
                                        <span class="trader-color__name">{{ $colorName !== '' ? $colorName : ($isArabic ? 'لون جملة' : 'Wholesale Color') }}</span>
                                        <span class="trader-pill" dir="ltr">{{ $isArabic ? 'المتاح: ' : 'Available: ' }}{{ $maxSeries }} {{ $isArabic ? 'سيرية' : 'series' }}</span>
                                    </div>

                                    @if ($seriesGroups->isEmpty())
                                        <div class="trader-empty">{{ $isArabic ? 'لا توجد سيريات مضبوطة لهذا اللون بعد.' : 'No series quantities have been configured for this color yet.' }}</div>
                                    @else
                                        @foreach ($seriesGroups as $seriesGroup => $seriesRows)
                                            @php
                                                $piecesPerSeries = $seriesRows->sum(fn ($row) => (int) $row->quantity);
                                                $seriesTotal = $piecesPerSeries * $unitPrice;
                                            @endphp

                                            <div class="trader-series-title">
                                                {{ $isArabic ? 'السيرية' : 'Series' }} <span dir="ltr">{{ $seriesGroup }}</span>
                                                - {{ $isArabic ? 'عدد القطع' : 'Pieces' }} <span dir="ltr">{{ $piecesPerSeries }}</span>
                                                - {{ $isArabic ? 'قيمة السيرية' : 'Series value' }} <span dir="ltr">{{ number_format($seriesTotal, 0) }} {{ $currency }}</span>
                                            </div>

                                            <div class="trader-matrix">
                                                <div class="trader-matrix__row">
                                                    @foreach ($seriesRows as $row)
                                                        <span class="trader-matrix__cell" dir="ltr">{{ $row->size_text }}</span>
                                                    @endforeach
                                                </div>
                                                <div class="trader-matrix__row">
                                                    @foreach ($seriesRows as $row)
                                                        <span class="trader-matrix__cell" dir="ltr">{{ (int) $row->quantity }}</span>
                                                    @endforeach
                                                </div>
                                            </div>

                                            <form method="POST" action="{{ route('front.trader.orders.store', $product) }}" class="trader-series-order" data-series-order data-pieces-per-series="{{ (int) $piecesPerSeries }}" data-unit-price="{{ (float) $unitPrice }}" data-currency="{{ $currency }}">
                                                @csrf
                                                <input type="hidden" name="product_wholesale_color_id" value="{{ (int) $availability->product_wholesale_color_id }}">
                                                <input type="hidden" name="series_group" value="{{ (int) $seriesGroup }}">

                                                <div class="trader-series-order__row">
                                                    <div>
                                                        <label>{{ $isArabic ? 'عدد السيريات المطلوبة' : 'Requested series count' }}</label>
                                                        <input type="number" name="series_count" value="1" min="1" max="{{ max(1, $maxSeries) }}" inputmode="numeric" dir="ltr" data-series-count {{ $maxSeries < 1 ? 'disabled' : '' }}>
                                                    </div>
                                                    <div>
                                                        <label>{{ $isArabic ? 'المتاح' : 'Available' }}</label>
                                                        <input type="text" value="{{ $maxSeries }}" dir="ltr" disabled>
                                                    </div>
                                                    <button type="submit" {{ $maxSeries < 1 ? 'disabled' : '' }}>
                                                        {{ $isArabic ? 'إضافة إلى طلب الجملة' : 'Add to Wholesale Cart' }}
                                                    </button>
                                                </div>

                                                <div class="trader-series-order__help">
                                                    {{ $isArabic ? 'سيتم إضافة السيرية كاملة إلى طلب الجملة المؤقت. يمكنك إضافة منتجات وسيريات أخرى قبل إرسال الطلب النهائي.' : 'The full series will be added to the wholesale cart. You can add more products and series before final submission.' }}
                                                </div>
                                                <div class="trader-series-order__summary">
                                                    <div class="trader-series-order__summary-item">
                                                        <span>{{ $isArabic ? 'إجمالي السيريات' : 'Series total' }}</span>
                                                        <strong dir="ltr" data-summary-series>1</strong>
                                                    </div>
                                                    <div class="trader-series-order__summary-item">
                                                        <span>{{ $isArabic ? 'إجمالي القطع' : 'Pieces total' }}</span>
                                                        <strong dir="ltr" data-summary-pieces>{{ (int) $piecesPerSeries }}</strong>
                                                    </div>
                                                    <div class="trader-series-order__summary-item">
                                                        <span>{{ $isArabic ? 'الإجمالي المتوقع' : 'Expected total' }}</span>
                                                        <strong dir="ltr" data-summary-total>{{ number_format($seriesTotal, 0) }} {{ $currency }}</strong>
                                                    </div>
                                                </div>
                                            </form>
                                        @endforeach
                                    @endif
                                </div>
                            @empty
                                <div class="trader-empty">{{ $isArabic ? 'هذا المنتج غير متاح لمجموعة حسابك حالياً.' : 'This product is not currently available for your trader group.' }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="trader-product__section">
                    <h2>{{ $isArabic ? 'وصف المنتج' : 'Product Description' }}</h2>
                    <p>{{ $isArabic ? ($product->description_ar ?: 'لم يتم إدخال وصف لهذا المنتج بعد.') : ($product->description_en ?: $product->description_ar ?: 'No description has been added for this product yet.') }}</p>
                </div>
            </div>
        </section>
    </main>
    @include('frontend.pages.trader.partials.footer')
@endsection

@push('scripts')
    <script>
        document.querySelectorAll('[data-series-order]').forEach((form) => {
            const input = form.querySelector('[data-series-count]');
            const seriesTarget = form.querySelector('[data-summary-series]');
            const piecesTarget = form.querySelector('[data-summary-pieces]');
            const totalTarget = form.querySelector('[data-summary-total]');
            const piecesPerSeries = Number(form.dataset.piecesPerSeries || 0);
            const unitPrice = Number(form.dataset.unitPrice || 0);
            const currency = form.dataset.currency || '';

            if (!input || !seriesTarget || !piecesTarget || !totalTarget) {
                return;
            }

            const formatNumber = (value) => new Intl.NumberFormat('en-US', {
                maximumFractionDigits: 0,
            }).format(Math.max(0, Number(value) || 0));

            const updateSummary = () => {
                const max = Number(input.max || 0);
                let seriesCount = Number(input.value || 1);

                if (!Number.isFinite(seriesCount) || seriesCount < 1) {
                    seriesCount = 1;
                }

                if (max > 0 && seriesCount > max) {
                    seriesCount = max;
                    input.value = String(max);
                }

                const pieces = piecesPerSeries * seriesCount;
                const total = pieces * unitPrice;

                seriesTarget.textContent = formatNumber(seriesCount);
                piecesTarget.textContent = formatNumber(pieces);
                totalTarget.textContent = `${formatNumber(total)} ${currency}`.trim();
            };

            input.addEventListener('input', updateSummary);
            input.addEventListener('change', updateSummary);
            updateSummary();
        });
    </script>
@endpush
