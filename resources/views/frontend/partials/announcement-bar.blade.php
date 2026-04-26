@php
    $items = collect($tickerItems ?? []);
    $socialLinks = collect($socialLinks ?? []);
    $singleItem = $items->count() === 1;
@endphp

<div class="announcement-bar bg_dark {{ $singleItem ? 'single-item' : '' }}">
    <div class="wrap-announcement-bar">
        <div class="box-sw-announcement-bar">
            @foreach (($singleItem ? $items : $items->concat($items)->concat($items)) as $item)
                <div class="announcement-bar-item">
                    <p>{{ $item['text'] ?? '' }}</p>
                </div>
            @endforeach
        </div>
        <div class="announcement-social tf-md-hidden">
            <ul class="tf-social-icon d-flex gap-10">
                @foreach ($socialLinks as $socialLink)
                    <li>
                        <a href="{{ $socialLink['url'] ?? '#' }}" class="box-icon w_28 round {{ $socialLink['anchor_class'] ?? 'social-facebook' }} bg_line">
                            <i class="icon fs-12 {{ $socialLink['icon_class'] ?? 'icon-link' }}"></i>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        <span class="icon-close close-announcement-bar"></span>
    </div>
</div>
