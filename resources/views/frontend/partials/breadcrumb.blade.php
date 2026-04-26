@php
    $items = collect($items ?? []);
@endphp

<nav class="tf-breadcrumb" aria-label="breadcrumb">
    <ol class="breadcrumb mb-0">
        @foreach ($items as $item)
            <li class="breadcrumb-item {{ $loop->last ? 'active' : '' }}">
                @if (! $loop->last && filled($item['url'] ?? null))
                    <a href="{{ $item['url'] }}">{{ $item['label'] ?? '' }}</a>
                @else
                    <span>{{ $item['label'] ?? '' }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
