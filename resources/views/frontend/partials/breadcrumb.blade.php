@php
    $items = collect($items ?? [])->values();
    $isArabic = app()->getLocale() === 'ar';
@endphp

<nav class="tf-breadcrumb" aria-label="breadcrumb" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
    <ol class="breadcrumb mb-0 d-flex align-items-center flex-wrap gap-2" dir="{{ $isArabic ? 'rtl' : 'ltr' }}">
        @foreach ($items as $item)
            <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }} d-inline-flex align-items-center gap-2">
                @if (! $loop->last && filled($item['url'] ?? null))
                    <a href="{{ $item['url'] }}">{{ $item['label'] ?? '' }}</a>
                @else
                    <span>{{ $item['label'] ?? '' }}</span>
                @endif

                @unless ($loop->last)
                    <span class="breadcrumb-divider" aria-hidden="true">/</span>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>