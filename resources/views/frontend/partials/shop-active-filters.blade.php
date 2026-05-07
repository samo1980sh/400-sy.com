@php
    $chips = collect($active_filter_chips ?? [])->filter(fn ($chip) => filled($chip['label'] ?? null))->values();
    $resetUrl = $filter_reset_url ?? route('front.products.index');
@endphp

@if ($chips->isNotEmpty())
    <div class="meta-filter-shop">
        @foreach ($chips as $chip)
            <a
                href="#"
                class="tf-btn style-2 btn-outline-2 radius-60"
                data-filter-chip
                data-filter-chip-type="{{ $chip['type'] ?? '' }}"
                data-filter-chip-value="{{ $chip['value'] ?? '' }}"
            >
                <span>{{ $chip['label'] ?? '' }}</span>
                <span aria-hidden="true">&nbsp;&times;</span>
            </a>
        @endforeach

        <a href="{{ $resetUrl }}" class="tf-btn style-2 btn-outline-dark radius-60 animate-hover-btn" data-filter-reset>
            <span>{{ app()->getLocale() === 'ar' ? 'إزالة الكل' : 'Clear all' }}</span>
        </a>
    </div>
@else
    <div class="meta-filter-shop"></div>
@endif
