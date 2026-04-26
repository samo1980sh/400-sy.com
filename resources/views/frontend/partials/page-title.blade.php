@php
    $background = $background ?? asset('images/slider/about-banner-02.jpg');
    $title = $title ?? '';
    $subtitle = $subtitle ?? '';
@endphp

<div class="tf-page-title" style="background-image:url({{ $background }});">
    <div class="container-full">
        <div class="row">
            <div class="col-12">
                <div class="heading text-center text_white">{{ $title }}</div>
                @if (filled($subtitle))
                    <p class="text-center text-2 text_black-2 mt_5 text_white">{{ $subtitle }}</p>
                @endif
                @include('frontend.partials.breadcrumb', ['items' => $breadcrumbs ?? []])
            </div>
        </div>
    </div>
</div>
