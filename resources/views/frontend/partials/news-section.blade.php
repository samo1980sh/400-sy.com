@php
    $newsItems = collect($newsItems ?? []);
@endphp

<section id="news-events" class="flat-spacing-8">
    <div class="container">
        <div class="flat-title mb_30 d-flex align-items-center justify-content-between">
            <h3 class="title">{{ __('front.sections.news') }}</h3>
        </div>
        <div class="row g-4">
            @foreach ($newsItems as $item)
                <div class="col-12 col-md-4">
                    <article class="blog-article-item">
                        <a href="{{ $item['url'] ?? '#' }}" class="article-image d-block overflow-hidden">
                            <img class="lazyload" data-src="{{ $item['image'] ?? '' }}" src="{{ $item['image'] ?? '' }}" alt="{{ $item['title'] ?? '' }}">
                        </a>
                        <div class="article-content">
                            <div class="meta">{{ $item['date'] ?? '' }}</div>
                            <h5>{{ $item['title'] ?? '' }}</h5>
                            @if (! empty($item['excerpt']))
                                <p>{{ $item['excerpt'] }}</p>
                            @endif
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</section>
