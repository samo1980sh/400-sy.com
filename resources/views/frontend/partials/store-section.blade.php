@php
    $branches = collect($branches ?? []);
@endphp

<section id="store-locations" class="flat-spacing-3 pb_5">
    <div class="container">
        <div class="flat-title wow fadeInUp" data-wow-delay="0s">
            <span class="title">{{ __('front.sections.visit_store') }}</span>
        </div>
        <div class="flat-tab-store flat-animate-tab">
            <ul class="widget-tab-2" role="tablist">
                @foreach ($branches as $index => $branch)
                    <li class="nav-tab-item" role="presentation">
                        <a href="#branch-{{ $index }}" class="{{ $index === 0 ? 'active' : '' }}" data-bs-toggle="tab">{{ $branch['name'] ?? '' }}</a>
                    </li>
                @endforeach
            </ul>
            <div class="tab-content">
                @foreach ($branches as $index => $branch)
                    <div class="tab-pane {{ $index === 0 ? 'active show' : '' }}" id="branch-{{ $index }}" role="tabpanel">
                        <div class="widget-card-store align-items-center tf-grid-layout md-col-2">
                            <div class="store-item-info">
                                <h5 class="store-heading">{{ $branch['name'] ?? '' }}</h5>
                                <div class="description">
                                    <p>{{ $branch['address'] ?? '' }}<br>{{ $branch['email'] ?? '' }}<br>{{ $branch['phone'] ?? '' }}</p>
                                    <p>{!! nl2br(e($branch['hours'] ?? '')) !!}</p>
                                </div>
                            </div>
                            <div class="store-img">
                                <img class="lazyload" data-src="{{ $branch['image'] ?? '' }}" src="{{ $branch['image'] ?? '' }}" alt="store-img">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
