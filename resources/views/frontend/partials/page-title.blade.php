@php
    $background = $background ?? null;
    $title = $title ?? '';
    $subtitle = $subtitle ?? '';
@endphp

<div class="tf-page-title {{ filled($background) ? '' : 'tf-page-title--plain' }}" @if (filled($background)) style="background-image:url({{ $background }});" @endif>
    <div class="container-full">
        <div class="row">
            <div class="col-12">
                <div class="heading text-center {{ filled($background) ? 'text_white' : 'text-black' }}">{{ $title }}</div>
                @if (filled($subtitle))
                    <p class="text-center text-2 text_black-2 mt_5 {{ filled($background) ? 'text_white' : 'text-black' }}">{{ $subtitle }}</p>
                @endif
                @include('frontend.partials.breadcrumb', ['items' => $breadcrumbs ?? []])
            </div>
        </div>
    </div>
</div>
