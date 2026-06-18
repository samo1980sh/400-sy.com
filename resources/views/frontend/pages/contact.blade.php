@extends('frontend.layouts.app')

@php
    $pageTitle = $page_title ?? __('front.contact.title');
    $contact = $contact ?? null;
    $isArabic = app()->getLocale() === 'ar';

    $companyName = $contact
        ? ($isArabic
            ? ($contact->company_name_ar ?: $contact->company_name_en ?: '')
            : ($contact->company_name_en ?: $contact->company_name_ar ?: ''))
        : '';
    $address = $contact
        ? ($isArabic
            ? ($contact->address_ar ?: $contact->address_en ?: '')
            : ($contact->address_en ?: $contact->address_ar ?: ''))
        : '';
    $workingHours = $contact
        ? ($isArabic
            ? ($contact->working_hours_ar ?: $contact->working_hours_en ?: '')
            : ($contact->working_hours_en ?: $contact->working_hours_ar ?: ''))
        : '';
    $notes = $contact
        ? ($isArabic
            ? ($contact->notes_ar ?: $contact->notes_en ?: '')
            : ($contact->notes_en ?: $contact->notes_ar ?: ''))
        : '';

    $phone = trim((string) ($contact?->phone ?? ''));
    $mobile = trim((string) ($contact?->mobile ?? ''));
    $whatsapp = trim((string) ($contact?->whatsapp ?? ''));
    $email = trim((string) ($contact?->email ?? ''));
    $mapUrl = trim((string) ($contact?->map_url ?? ''));
    $whatsappDigits = preg_replace('/\D+/', '', $whatsapp) ?: '';
    $whatsappUrl = str_starts_with($whatsapp, 'http://') || str_starts_with($whatsapp, 'https://')
        ? $whatsapp
        : ($whatsappDigits !== '' ? 'https://wa.me/' . $whatsappDigits : '');

    $contactSocialLinks = collect([
        [
            'platform' => 'facebook',
            'title' => 'Facebook',
            'url' => trim((string) ($contact?->facebook_url ?? '')),
        ],
        [
            'platform' => 'instagram',
            'title' => 'Instagram',
            'url' => trim((string) ($contact?->instagram_url ?? '')),
        ],
        [
            'platform' => 'x',
            'title' => 'X',
            'url' => trim((string) ($contact?->x_url ?? '')),
        ],
        [
            'platform' => 'youtube',
            'title' => 'YouTube',
            'url' => trim((string) ($contact?->youtube_url ?? '')),
        ],
        [
            'platform' => 'whatsapp',
            'title' => 'WhatsApp',
            'url' => $whatsappUrl,
        ],
    ]);

    $dynamicSocialLinks = collect($social_links ?? [])->map(function ($link): array {
        $anchorClass = strtolower((string) ($link['anchor_class'] ?? ''));
        $iconClass = strtolower((string) ($link['icon_class'] ?? ''));
        $title = strtolower((string) ($link['title'] ?? ''));
        $signature = $anchorClass . ' ' . $iconClass . ' ' . $title;

        $platform = match (true) {
            str_contains($signature, 'facebook'), str_contains($signature, 'icon-fb') => 'facebook',
            str_contains($signature, 'instagram') => 'instagram',
            str_contains($signature, 'whatsapp') => 'whatsapp',
            str_contains($signature, 'youtube') => 'youtube',
            str_contains($signature, 'twitter'), str_contains($signature, 'twiter'), $title === 'x' => 'x',
            str_contains($signature, 'tiktok') => 'tiktok',
            str_contains($signature, 'linkedin') => 'linkedin',
            str_contains($signature, 'snapchat') => 'snapchat',
            default => 'link',
        };

        return [
            'platform' => $platform,
            'title' => (string) ($link['title'] ?? ucfirst($platform)),
            'url' => trim((string) ($link['url'] ?? '')),
            'icon_class' => (string) ($link['icon_class'] ?? ''),
        ];
    });

    $socialLinks = $contactSocialLinks
        ->concat($dynamicSocialLinks)
        ->filter(fn (array $link): bool => filled($link['url'] ?? null) && ($link['url'] ?? '#') !== '#')
        ->unique(fn (array $link): string => ($link['platform'] ?? 'link') !== 'link'
            ? (string) $link['platform']
            : (string) $link['url'])
        ->values();

    $hasMainOffice = $address !== '' || $phone !== '' || $email !== '';
    $hasCustomerService = $mobile !== '' || $email !== '';
    $hasWorkingHours = trim(strip_tags($workingHours)) !== '';
    $hasNotes = trim(strip_tags($notes)) !== '';
    $hasSocialLinks = $socialLinks->isNotEmpty();
    $hasContactDetails = $hasMainOffice || $hasCustomerService || $hasWorkingHours || $hasNotes || $hasSocialLinks;
    $infoIntro = $companyName !== ''
        ? __('front.contact.info_intro', ['company' => $companyName])
        : __('front.contact.info_intro_generic');
@endphp

@section('title', $pageTitle)
@section('meta_description', $page_meta_description ?? $pageTitle)

@section('content')
    @include('frontend.partials.announcement-bar', [
        'tickerItems' => $ticker_items ?? [],
        'socialLinks' => $social_links ?? [],
    ])

    @include('frontend.partials.header', [
        'navCategories' => $nav_categories ?? [],
        'currencyOptions' => $currency_options ?? [],
        'cartCount' => $cart_count ?? 0,
        'wishlistCount' => $wishlist_count ?? 0,
        'wishlistUrl' => $wishlist_url ?? route('front.wishlist.index'),
        'siteName' => $site_name ?? __('front.brand'),
    ])

    @include('frontend.partials.page-title', [
        'title' => $pageTitle,
        'subtitle' => $page_subtitle ?? '',
        'breadcrumbs' => $breadcrumb_items ?? [],
        'background' => $page_title_background ?? null,
    ])

    <section class="flat-spacing-10">
        <div class="container">
            <div class="row g-4 g-lg-5 align-items-start">
                <div class="col-12 col-lg-5">
                    <div class="border rounded-3 p-4 p-lg-5 h-100">
                        <div class="mb-4">
                            <h3 class="mb-2">{{ __('front.contact.info_title') }}</h3>
                            <p class="mb-0 text-muted">{{ $infoIntro }}</p>
                        </div>

                        @if ($hasContactDetails)
                            <div class="d-grid gap-4">
                                @if ($hasMainOffice)
                                    <div>
                                        <h5 class="mb-3">{{ __('front.contact.main_office') }}</h5>

                                        @if ($address !== '')
                                            <p class="mb-2 text-break">{{ $address }}</p>
                                        @endif

                                        @if ($phone !== '')
                                            <p class="mb-2">
                                                <span class="fw-semibold">{{ __('front.contact.landline') }}:</span>
                                                <a href="tel:{{ $phone }}" class="text-break">
                                                    <span dir="ltr">{{ $phone }}</span>
                                                </a>
                                            </p>
                                        @endif

                                        @if ($email !== '')
                                            <p class="mb-2">
                                                <span class="fw-semibold">{{ __('front.contact.email') }}:</span>
                                                <a href="mailto:{{ $email }}" class="text-break d-inline-block" dir="ltr">{{ $email }}</a>
                                            </p>
                                        @endif

                                        @if ($mapUrl !== '')
                                            <a href="{{ $mapUrl }}" target="_blank" rel="noopener noreferrer" class="link d-inline-block mt-1">
                                                {{ __('front.contact.open_map') }}
                                            </a>
                                        @endif
                                    </div>
                                @endif

                                @if ($hasCustomerService)
                                    <div>
                                        <h5 class="mb-3">{{ __('front.contact.customer_service') }}</h5>

                                        @if ($mobile !== '')
                                            <p class="mb-2">
                                                <span class="fw-semibold">{{ __('front.contact.mobile') }}:</span>
                                                <a href="tel:{{ $mobile }}" class="text-break">
                                                    <span dir="ltr">{{ $mobile }}</span>
                                                </a>
                                            </p>
                                        @endif

                                        @if ($email !== '')
                                            <p class="mb-2">
                                                <span class="fw-semibold">{{ __('front.contact.email') }}:</span>
                                                <a href="mailto:{{ $email }}" class="text-break d-inline-block" dir="ltr">{{ $email }}</a>
                                            </p>
                                        @endif

                                    </div>
                                @endif

                                @if ($hasWorkingHours)
                                    <div>
                                        <h5 class="mb-2">{{ __('front.contact.working_hours') }}</h5>
                                        <div>{!! $workingHours !!}</div>
                                    </div>
                                @endif

                                @if ($hasNotes)
                                    <div>{!! $notes !!}</div>
                                @endif

                                @if ($hasSocialLinks)
                                    <div>
                                        <h5 class="mb-3">{{ __('front.contact.follow_us') }}</h5>
                                        <ul class="tf-social-icon contact-social-list d-flex flex-wrap gap-10">
                                            @foreach ($socialLinks as $link)
                                                @php($platform = $link['platform'] ?? 'link')
                                                <li>
                                                    <a
                                                        href="{{ $link['url'] }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="box-icon w_34 round social-line contact-social-link contact-social-{{ $platform }}"
                                                        aria-label="{{ $link['title'] ?? ucfirst($platform) }}"
                                                        title="{{ $link['title'] ?? ucfirst($platform) }}"
                                                    >
                                                        @switch($platform)
                                                            @case('facebook')
                                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                                    <path fill="currentColor" d="M13.5 22v-8.8h3l.5-3.4h-3.5V7.6c0-1 .3-1.8 1.8-1.8H17V2.7c-.3 0-1.4-.1-2.7-.1-2.7 0-4.6 1.7-4.6 4.8v2.4H7v3.4h2.7V22h3.8Z"/>
                                                                </svg>
                                                                @break
                                                            @case('instagram')
                                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                                    <rect x="3.5" y="3.5" width="17" height="17" rx="5" fill="none" stroke="currentColor" stroke-width="2"/>
                                                                    <circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/>
                                                                    <circle cx="17.4" cy="6.7" r="1.2" fill="currentColor"/>
                                                                </svg>
                                                                @break
                                                            @case('whatsapp')
                                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                                    <path fill="currentColor" d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.64-2.05-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.19 1.87.12.57-.08 1.76-.72 2.01-1.42.25-.7.25-1.3.17-1.42-.07-.12-.27-.2-.57-.35ZM12.04 2a9.84 9.84 0 0 0-8.42 14.92L2 22l5.23-1.57A9.95 9.95 0 1 0 12.04 2Zm0 17.95a8.1 8.1 0 0 1-4.13-1.13l-.3-.18-3.1.93.95-3.02-.2-.31a8.08 8.08 0 1 1 6.78 3.71Z"/>
                                                                </svg>
                                                                @break
                                                            @case('youtube')
                                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                                    <path fill="currentColor" d="M21.6 7.2a3 3 0 0 0-2.1-2.1C17.7 4.6 12 4.6 12 4.6s-5.7 0-7.5.5a3 3 0 0 0-2.1 2.1A31 31 0 0 0 2 12a31 31 0 0 0 .4 4.8 3 3 0 0 0 2.1 2.1c1.8.5 7.5.5 7.5.5s5.7 0 7.5-.5a3 3 0 0 0 2.1-2.1A31 31 0 0 0 22 12a31 31 0 0 0-.4-4.8ZM10 15.2V8.8l5.5 3.2-5.5 3.2Z"/>
                                                                </svg>
                                                                @break
                                                            @case('x')
                                                                <span class="contact-social-x" aria-hidden="true">X</span>
                                                                @break
                                                            @default
                                                                @if (filled($link['icon_class'] ?? null))
                                                                    <i class="{{ $link['icon_class'] }}" aria-hidden="true"></i>
                                                                @else
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                                                        <path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M10 14a5 5 0 0 0 7.1.1l2-2a5 5 0 0 0-7.1-7.1l-1.1 1.1M14 10a5 5 0 0 0-7.1-.1l-2 2A5 5 0 0 0 12 19l1.1-1.1"/>
                                                                    </svg>
                                                                @endif
                                                        @endswitch
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="alert alert-light border mb-0" role="status">
                                {{ __('front.contact.info_unavailable') }}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-12 col-lg-7">
                    <div class="border rounded-3 p-4 p-lg-5">
                        <div class="mb-4">
                            <h3 class="mb-2">{{ __('front.contact.form_title') }}</h3>
                            <p class="mb-0 text-muted">{{ __('front.contact.form_intro') }}</p>
                        </div>

                        <div
                            class="alert d-none"
                            role="status"
                            aria-live="polite"
                            tabindex="-1"
                            data-contact-feedback
                        ></div>

                        @if (session('contact_success'))
                            <div class="alert alert-success" role="alert" data-contact-server-feedback>
                                {{ session('contact_success') }}
                            </div>
                        @endif

                        @if (session('contact_error'))
                            <div class="alert alert-danger" role="alert" data-contact-server-feedback>
                                {{ session('contact_error') }}
                            </div>
                        @endif

                        @if ($errors->contact->any())
                            <div class="alert alert-danger" role="alert" data-contact-server-feedback>
                                <div class="fw-semibold mb-2">{{ __('front.contact.validation_title') }}</div>
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->contact->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form
                            method="POST"
                            action="{{ route('front.contact.send') }}"
                            novalidate
                            data-contact-form
                            data-validation-message="{{ __('front.contact.validation_title') }}"
                            data-rate-limit-message="{{ __('front.contact.rate_limited') }}"
                            data-network-error-message="{{ __('front.contact.network_error') }}"
                            data-unexpected-error-message="{{ __('front.contact.unexpected_error') }}"
                        >
                            @csrf

                            <div class="visually-hidden" aria-hidden="true">
                                <label for="contact-website">{{ __('front.contact.website') }}</label>
                                <input id="contact-website" type="text" name="website" value="" tabindex="-1" autocomplete="off">
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="contact-name" class="form-label">{{ __('front.contact.name') }} *</label>
                                    <input id="contact-name" type="text" name="name" value="" class="form-control @error('name', 'contact') is-invalid @enderror" maxlength="120" autocomplete="name" required aria-describedby="contact-name-error">
                                    <div id="contact-name-error" class="invalid-feedback" data-contact-error="name">@error('name', 'contact'){{ $message }}@enderror</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="contact-email" class="form-label">{{ __('front.contact.email') }} *</label>
                                    <input id="contact-email" type="email" name="email" value="" class="form-control @error('email', 'contact') is-invalid @enderror" maxlength="255" autocomplete="email" dir="ltr" required aria-describedby="contact-email-error">
                                    <div id="contact-email-error" class="invalid-feedback" data-contact-error="email">@error('email', 'contact'){{ $message }}@enderror</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="contact-phone" class="form-label">{{ __('front.contact.phone') }} *</label>
                                    <input id="contact-phone" type="tel" name="phone" value="" class="form-control @error('phone', 'contact') is-invalid @enderror" maxlength="30" autocomplete="tel" dir="ltr" required aria-describedby="contact-phone-error">
                                    <div id="contact-phone-error" class="invalid-feedback" data-contact-error="phone">@error('phone', 'contact'){{ $message }}@enderror</div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label for="contact-subject" class="form-label">{{ __('front.contact.subject') }} *</label>
                                    <input id="contact-subject" type="text" name="subject" value="" class="form-control @error('subject', 'contact') is-invalid @enderror" maxlength="160" required aria-describedby="contact-subject-error">
                                    <div id="contact-subject-error" class="invalid-feedback" data-contact-error="subject">@error('subject', 'contact'){{ $message }}@enderror</div>
                                </div>

                                <div class="col-12">
                                    <label for="contact-message" class="form-label">{{ __('front.contact.message') }} *</label>
                                    <textarea id="contact-message" name="message" rows="7" class="form-control @error('message', 'contact') is-invalid @enderror" maxlength="5000" required aria-describedby="contact-message-error"></textarea>
                                    <div id="contact-message-error" class="invalid-feedback" data-contact-error="message">@error('message', 'contact'){{ $message }}@enderror</div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="tf-btn btn-fill justify-content-center fw-6 animate-hover-btn" data-contact-submit>
                                        <span data-contact-submit-label>{{ __('front.contact.send') }}</span>
                                        <span class="d-none align-items-center gap-2" data-contact-submit-loading>
                                            <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                            <span>{{ __('front.contact.sending') }}</span>
                                        </span>
                                        <i class="icon icon-arrow1-top-left" data-contact-submit-icon></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @include('frontend.partials.footer', [
        'contact' => $contact ?? null,
        'socialLinks' => $social_links ?? [],
        'footerPages' => $footer_pages ?? [],
        'collections' => $collections ?? [],
    ])

    @include('frontend.partials.toolbar-bottom', [
        'cartCount' => $cart_count ?? 0,
        'wishlistCount' => $wishlist_count ?? 0,
        'wishlistUrl' => $wishlist_url ?? route('front.wishlist.index'),
    ])

    @include('frontend.partials.mobile-menu', [
        'navCategories' => $nav_categories ?? [],
        'quickLinks' => $quick_links ?? [],
    ])

    @include('frontend.partials.search-canvas', ['quickLinks' => $quick_links ?? []])
    @include('frontend.partials.shopping-cart', ['cartState' => $cart_state ?? []])
    @include('frontend.partials.auth-modals')
    @include('frontend.partials.quick-add')
    @include('frontend.partials.quick-view')
    @include('frontend.partials.find-size')
@endsection

@push('styles')
    <style>
        .contact-social-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .contact-social-list .contact-social-link {
            color: #fff;
            border: 1px solid transparent;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }

        .contact-social-list .contact-social-facebook {
            background: #1877f2;
            border-color: #1877f2;
        }

        .contact-social-list .contact-social-instagram {
            background: linear-gradient(135deg, #feda75 0%, #fa7e1e 28%, #d62976 55%, #962fbf 78%, #4f5bd5 100%);
        }

        .contact-social-list .contact-social-whatsapp {
            background: #25d366;
            border-color: #25d366;
        }

        .contact-social-list .contact-social-youtube {
            background: #ff0000;
            border-color: #ff0000;
        }

        .contact-social-list .contact-social-x {
            background: #000;
            border-color: #000;
        }

        .contact-social-list .contact-social-tiktok {
            color: #fff;
            background: #010101;
            border-color: #010101;
        }

        .contact-social-list .contact-social-link:hover,
        .contact-social-list .contact-social-link:focus-visible {
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(17, 17, 17, 0.18);
            opacity: 0.92;
        }

        .contact-social-list .contact-social-link svg {
            display: block;
            width: 17px;
            height: 17px;
            flex: 0 0 17px;
        }

        .contact-social-list .contact-social-x {
            font-size: 15px;
            font-weight: 700;
            line-height: 1;
        }
    </style>
@endpush

@push('scripts')
    @include('frontend.partials.product-scripts')

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-contact-form]');

            if (! form || typeof window.fetch !== 'function') {
                return;
            }

            const feedback = document.querySelector('[data-contact-feedback]');
            const submitButton = form.querySelector('[data-contact-submit]');
            const submitLabel = form.querySelector('[data-contact-submit-label]');
            const submitLoading = form.querySelector('[data-contact-submit-loading]');
            const submitIcon = form.querySelector('[data-contact-submit-icon]');
            let isSubmitting = false;

            const clearFieldErrors = function () {
                form.querySelectorAll('.is-invalid').forEach(function (field) {
                    field.classList.remove('is-invalid');
                    field.removeAttribute('aria-invalid');
                });

                form.querySelectorAll('[data-contact-error]').forEach(function (errorElement) {
                    errorElement.textContent = '';
                });
            };

            const hideFeedback = function () {
                if (! feedback) {
                    return;
                }

                feedback.classList.add('d-none');
                feedback.classList.remove('alert-success', 'alert-danger');
                feedback.textContent = '';
            };

            const showFeedback = function (message, type) {
                if (! feedback) {
                    return;
                }

                feedback.classList.remove('d-none', 'alert-success', 'alert-danger');
                feedback.classList.add(type === 'success' ? 'alert-success' : 'alert-danger');
                feedback.textContent = message || form.dataset.unexpectedErrorMessage || '';

                try {
                    feedback.focus({ preventScroll: true });
                } catch (error) {
                    feedback.focus();
                }
            };

            const setSubmitting = function (submitting) {
                isSubmitting = submitting;

                if (! submitButton) {
                    return;
                }

                submitButton.disabled = submitting;
                submitButton.setAttribute('aria-busy', submitting ? 'true' : 'false');

                submitLabel?.classList.toggle('d-none', submitting);
                submitLoading?.classList.toggle('d-none', ! submitting);
                submitLoading?.classList.toggle('d-inline-flex', submitting);
                submitIcon?.classList.toggle('d-none', submitting);
            };

            const showValidationErrors = function (errors) {
                let firstInvalidField = null;

                Object.entries(errors || {}).forEach(function ([fieldName, messages]) {
                    const field = form.elements.namedItem(fieldName);
                    const errorElement = form.querySelector('[data-contact-error="' + fieldName + '"]');
                    const message = Array.isArray(messages) ? messages[0] : messages;

                    if (field instanceof HTMLElement) {
                        field.classList.add('is-invalid');
                        field.setAttribute('aria-invalid', 'true');
                        firstInvalidField ??= field;
                    }

                    if (errorElement) {
                        errorElement.textContent = message || '';
                    }
                });

                showFeedback(form.dataset.validationMessage || '', 'error');
                firstInvalidField?.focus({ preventScroll: true });
            };

            form.addEventListener('submit', async function (event) {
                event.preventDefault();

                if (isSubmitting) {
                    return;
                }

                clearFieldErrors();
                hideFeedback();
                document.querySelectorAll('[data-contact-server-feedback]').forEach(function (element) {
                    element.classList.add('d-none');
                });
                setSubmitting(true);

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form),
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    let payload = {};

                    try {
                        payload = await response.json();
                    } catch (error) {
                        payload = {};
                    }

                    if (response.ok && payload.ok) {
                        form.reset();
                        clearFieldErrors();
                        showFeedback(payload.message || '', 'success');
                        return;
                    }

                    if (response.status === 422) {
                        showValidationErrors(payload.errors || {});
                        return;
                    }

                    if (response.status === 429) {
                        showFeedback(form.dataset.rateLimitMessage || payload.message || '', 'error');
                        return;
                    }

                    showFeedback(payload.message || form.dataset.unexpectedErrorMessage || '', 'error');
                } catch (error) {
                    showFeedback(form.dataset.networkErrorMessage || '', 'error');
                } finally {
                    setSubmitting(false);
                }
            });
        });
    </script>
@endpush
