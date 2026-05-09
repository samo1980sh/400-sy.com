@php
    $chips = collect($active_filter_chips ?? [])->filter(fn ($chip) => filled($chip['label'] ?? null))->values();
    $contextChip = filled($category_context_chip['label'] ?? null) ? $category_context_chip : null;
    $resetUrl = $filter_reset_url ?? request()->url();
@endphp

@if ($chips->isNotEmpty() || $contextChip !== null)
    <div class="meta-filter-shop">
        @if ($contextChip !== null)
            <span class="tf-btn style-2 btn-outline-2 radius-60">
                <span>{{ $contextChip['label'] }}</span>
            </span>
        @endif

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

        <a href="{{ $resetUrl }}" class="tf-btn style-2 btn-outline-dark radius-60 animate-hover-btn">
            <span>{{ app()->getLocale() === 'ar' ? 'إزالة الكل' : 'Clear all' }}</span>
        </a>
    </div>
@else
    <div class="meta-filter-shop"></div>
@endif
