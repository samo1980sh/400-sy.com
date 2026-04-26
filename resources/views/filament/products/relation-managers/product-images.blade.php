@php
    use Illuminate\View\ComponentAttributeBag;

    $colors = collect($colors ?? [])
        ->filter(fn (array $color): bool => filled($color['id'] ?? null))
        ->values();

    $initialColorId = $colors->first()['id'] ?? null;
@endphp

<div
    x-data="{
        activeColor: @js($initialColorId),
    }"
    class="space-y-4"
>
    <style>
        [x-cloak] {
            display: none !important;
        }

        .product-images-shell,
        .product-images-panel,
        .product-images-tab,
        .product-image-card,
        .product-images-lightbox-frame {
            transition:
                background-color 0.2s ease,
                border-color 0.2s ease,
                color 0.2s ease,
                box-shadow 0.2s ease,
                transform 0.2s ease;
        }

        .product-images-shell {
            border: 1px solid rgb(229 231 235);
            border-radius: 18px;
            background:
                linear-gradient(180deg, rgba(255,255,255,0.98), rgba(249,250,251,0.98)),
                radial-gradient(circle at top left, rgba(249,115,22,0.08), transparent 30%);
            padding: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05);
        }

        html.dark .product-images-shell {
            border-color: rgb(55 65 81);
            background:
                linear-gradient(180deg, rgba(17,24,39,0.95), rgba(17,24,39,0.98)),
                radial-gradient(circle at top left, rgba(249,115,22,0.10), transparent 30%);
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.32);
        }

        .product-images-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .product-images-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgb(229 231 235);
            border-radius: 9999px;
            padding: 9px 14px;
            background: #fff;
            color: rgb(55 65 81);
            font-size: 13px;
            font-weight: 600;
            line-height: 1;
            transition: all 0.18s ease;
        }

        html.dark .product-images-tab {
            border-color: rgb(55 65 81);
            background: rgb(17 24 39);
            color: rgb(229 231 235);
        }

        .product-images-tab:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(15, 23, 42, 0.08);
        }

        .product-images-tab.is-active {
            background: rgb(249 115 22);
            border-color: rgb(249 115 22);
            color: #fff;
            box-shadow: 0 8px 18px rgba(249, 115, 22, 0.24);
        }

        .product-images-panel {
            border: 1px solid rgb(229 231 235);
            border-radius: 16px;
            background: rgba(255,255,255,0.96);
            padding: 16px;
        }

        html.dark .product-images-panel {
            border-color: rgb(55 65 81);
            background: rgba(17,24,39,0.96);
        }

        .product-images-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgb(243 244 246);
        }

        html.dark .product-images-head {
            border-bottom-color: rgb(55 65 81);
        }

        .product-images-headline {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .product-images-head h4 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            color: rgb(17 24 39);
        }

        html.dark .product-images-head h4 {
            color: rgb(243 244 246);
        }

        .product-images-status {
            display: inline-flex;
            align-items: center;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
            border: 1px solid transparent;
        }

        .product-images-status.is-active {
            background: rgba(34, 197, 94, 0.12);
            border-color: rgba(34, 197, 94, 0.25);
            color: rgb(22, 163, 74);
        }

        .product-images-status.is-inactive {
            background: rgba(239, 68, 68, 0.10);
            border-color: rgba(239, 68, 68, 0.20);
            color: rgb(220, 38, 38);
        }

        html.dark .product-images-status.is-active {
            background: rgba(34, 197, 94, 0.16);
            color: rgb(134, 239, 172);
        }

        html.dark .product-images-status.is-inactive {
            background: rgba(239, 68, 68, 0.14);
            color: rgb(252, 165, 165);
        }

        .product-images-head .hint {
            font-size: 12px;
            color: rgb(107 114 128);
            margin-top: 4px;
        }

        html.dark .product-images-head .hint {
            color: rgb(156 163 175);
        }

        .product-images-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(110px, 110px));
            gap: 12px;
        }

        .product-image-card {
            width: 110px;
            border: 1px solid rgb(229 231 235);
            border-radius: 14px;
            overflow: hidden;
            background: rgb(255 255 255);
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
        }

        html.dark .product-image-card {
            border-color: rgb(55 65 81);
            background: rgb(17 24 39);
        }

        .product-image-card:hover {
            transform: translateY(-2px);
            border-color: rgb(251 146 60);
            box-shadow: 0 10px 18px rgba(15, 23, 42, 0.10);
        }

        .product-image-thumb {
            display: block;
            width: 110px;
            height: 110px;
            object-fit: cover;
            background: #f9fafb;
        }

        html.dark .product-image-thumb {
            background: rgb(31 41 55);
        }

        .product-image-caption {
            padding: 7px 8px 8px;
            font-size: 11px;
            color: rgb(107 114 128);
            text-align: center;
        }

        html.dark .product-image-caption {
            color: rgb(156 163 175);
        }
    </style>

    @if ($colors->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-500">
            لا توجد صور مرتبطة بهذا المنتج
        </div>
    @else
        <div class="product-images-shell">
            <div class="product-images-tabs" style="margin-bottom: 18px;">
            @foreach ($colors as $color)
                <button
                    type="button"
                    x-on:click="activeColor = {{ $color['id'] }}"
                    class="product-images-tab"
                    :class="activeColor === {{ $color['id'] }} ? 'is-active' : ''"
                >
                    <span>{{ $color['name'] }}</span>
                </button>
            @endforeach
            </div>

            @foreach ($colors as $color)
                @php
                    $modalId = 'product-images-lightbox-' . $color['id'];
                    $isActive = ($color['status'] ?? 'inactive') === 'active';
                @endphp

                <div
                    x-show="activeColor === {{ $color['id'] }}"
                    x-cloak
                    x-data="{
                        currentIndex: 0,
                        images: @js($color['zoom_urls'] ?? []),
                        modalId: @js($modalId),
                        openImage(index) {
                            this.currentIndex = index;
                            this.$dispatch('open-modal', { id: this.modalId });
                        },
                        closeImage() {
                            this.$dispatch('close-modal', { id: this.modalId });
                        },
                        previousImage() {
                            if (! this.images.length) return;
                            this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
                        },
                        nextImage() {
                            if (! this.images.length) return;
                            this.currentIndex = (this.currentIndex + 1) % this.images.length;
                        },
                    }"
                    class="product-images-panel"
                >
                    <div class="product-images-head">
                        <div>
                            <div class="product-images-headline">
                                <h4>{{ $color['name'] }}</h4>
                                <span class="product-images-status {{ $isActive ? 'is-active' : 'is-inactive' }}">
                                    {{ $isActive ? 'فعال' : 'غير فعال' }}
                                </span>
                            </div>
                            <div class="hint">انقر على أي صورة لفتحها بحجم كامل</div>
                        </div>
                    </div>

                    @if (($color['thumb_urls'] ?? []) === [])
                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-sm text-gray-500">
                            لا توجد صور لهذا اللون
                        </div>
                    @else
                        <div class="product-images-grid">
                            @foreach ($color['thumb_urls'] as $index => $url)
                                <button
                                    type="button"
                                    x-on:click="openImage({{ $index }})"
                                    class="product-image-card text-start"
                                >
                                    <img
                                        src="{{ $url }}"
                                        alt=""
                                        class="product-image-thumb"
                                    >
                                    <div class="product-image-caption">
                                        {{ $index === 0 ? 'الصورة الرئيسية' : 'صورة ' . $index }}
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <div class="product-images-lightbox-overlay">
                        <x-filament::modal
                            :id="$modalId"
                            width="screen"
                            :close-by-clicking-away="true"
                            :close-by-escaping="true"
                            :close-button="false"
                            :extra-modal-window-attribute-bag="new ComponentAttributeBag([
                                'class' => 'product-images-lightbox-window bg-transparent shadow-none ring-0 border-0 p-0 overflow-visible',
                            ])"
                        >
                        <div class="product-images-lightbox" x-cloak>
                            <style>
                                .product-images-lightbox-shell {
                                    position: relative;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    min-height: 86vh;
                                    padding: 24px;
                                }

                                .product-images-lightbox-frame {
                                    position: relative;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    width: min(90vw, 1100px);
                                    height: min(84vh, 760px);
                                    border-radius: 28px;
                                    background: rgba(15, 23, 42, 0.38);
                                    backdrop-filter: blur(10px);
                                    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.30);
                                    overflow: hidden;
                                }

                                .product-images-lightbox-image {
                                    max-width: 100%;
                                    max-height: 100%;
                                    object-fit: contain;
                                }

                                .product-images-lightbox-control {
                                    position: absolute;
                                    top: 50%;
                                    transform: translateY(-50%);
                                    width: 48px;
                                    height: 48px;
                                    border-radius: 9999px;
                                    border: 1px solid rgba(255,255,255,0.18);
                                    background: rgba(15, 23, 42, 0.55);
                                    color: #fff;
                                    display: inline-flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-size: 26px;
                                    line-height: 1;
                                    transition: transform 0.18s ease, background 0.18s ease;
                                }

                                .product-images-lightbox-control:hover {
                                    background: rgba(15, 23, 42, 0.78);
                                }

                                .product-images-lightbox-prev {
                                    right: 18px;
                                }

                                .product-images-lightbox-next {
                                    left: 18px;
                                }

                                .product-images-lightbox-close {
                                    position: absolute;
                                    top: 18px;
                                    right: 18px;
                                    width: 44px;
                                    height: 44px;
                                    border-radius: 9999px;
                                    border: 1px solid rgba(255,255,255,0.18);
                                    background: rgba(15, 23, 42, 0.55);
                                    color: #fff;
                                    display: inline-flex;
                                    align-items: center;
                                    justify-content: center;
                                    font-size: 26px;
                                    line-height: 1;
                                }

                                .product-images-lightbox-close:hover {
                                    background: rgba(15, 23, 42, 0.78);
                                }

                                .product-images-lightbox-overlay .fi-modal-close-overlay {
                                    backdrop-filter: blur(8px);
                                    background: rgba(2, 6, 23, 0.55);
                                }

                                .product-images-lightbox-window {
                                    width: 100vw !important;
                                    max-width: 100vw !important;
                                    background: transparent !important;
                                    box-shadow: none !important;
                                    border: 0 !important;
                                }
                            </style>

                            <div class="product-images-lightbox-shell" x-on:click.self="closeImage()">
                                <div class="product-images-lightbox-frame">
                                    <button
                                        type="button"
                                        class="product-images-lightbox-close"
                                        x-on:click="closeImage()"
                                        aria-label="إغلاق"
                                    >
                                        ×
                                    </button>

                                    <button
                                        type="button"
                                        class="product-images-lightbox-control product-images-lightbox-prev"
                                        x-on:click="previousImage()"
                                        aria-label="الصورة السابقة"
                                    >
                                        ‹
                                    </button>

                                    <img
                                        x-bind:src="images[currentIndex]"
                                        alt=""
                                        class="product-images-lightbox-image"
                                    >

                                    <button
                                        type="button"
                                        class="product-images-lightbox-control product-images-lightbox-next"
                                        x-on:click="nextImage()"
                                        aria-label="الصورة التالية"
                                    >
                                        ›
                                    </button>
                                </div>
                            </div>
                        </div>
                        </x-filament::modal>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
