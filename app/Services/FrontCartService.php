<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductColor;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class FrontCartService
{
    public const SESSION_KEY = 'front.cart.items';

    public function __construct(protected ProductImageCatalogService $imageCatalog)
    {
    }

    public function state(): array
    {
        $items = collect(session()->get(self::SESSION_KEY, []))
            ->values()
            ->map(fn (array $item): array => $this->normalizeItem($item))
            ->filter(fn (array $item): bool => filled($item['key'] ?? null))
            ->values();

        $count = $items->count();
        $subtotal = (int) $items->sum(fn (array $item): int => (int) ($item['unit_price'] ?? $item['base_price'] ?? 0) * (int) ($item['qty'] ?? 0));
        $currency = strtoupper((string) (
            session('selectedCurrency')
            ?: (app()->bound('currentCurrency') ? app('currentCurrency') : null)
            ?: ($items->first()['base_currency'] ?? 'SYP')
        ));

        return [
            'items' => $items->all(),
            'count' => $count,
            'subtotal' => $subtotal,
            'currency' => $currency,
            'subtotal_label' => number_format($subtotal, 0) . ' ' . $currency,
        ];
    }

    public function checkoutState(): array
    {
        $storedItems = $this->storedItems();

        if ($storedItems === []) {
            return $this->state();
        }

        $productIds = collect($storedItems)
            ->pluck('product_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $products = Product::query()
            ->visibleToFrontendVisitor()
            ->where('is_active', true)
            ->whereIn('id', $productIds)
            ->with(['variants.size', 'variants.productColor', 'productColors.filterColor'])
            ->get()
            ->keyBy(fn (Product $product): int => (int) $product->getKey());

        $refreshedItems = [];

        foreach ($storedItems as $storedItem) {
            $product = $products->get((int) ($storedItem['product_id'] ?? 0));
            $quantity = max(1, min(99, (int) ($storedItem['qty'] ?? 1)));

            if (! $product instanceof Product) {
                throw ValidationException::withMessages([
                    'cart' => __('front.checkout.cart_changed'),
                ]);
            }

            $requestedColorId = (int) ($storedItem['color_id'] ?? 0);

            if ($requestedColorId > 0) {
                $requestedColor = $product->productColors
                    ->first(fn ($color): bool => (int) ($color->id ?? 0) === $requestedColorId);

                if (
                    ! $requestedColor instanceof ProductColor
                    || (string) ($requestedColor->status ?? 'active') !== 'active'
                ) {
                    throw ValidationException::withMessages([
                        'cart' => __('front.checkout.cart_changed'),
                    ]);
                }
            }

            $requestedVariantId = (int) ($storedItem['variant_id'] ?? 0);

            if ($requestedVariantId > 0) {
                $requestedVariant = $product->variants
                    ->first(fn ($variant): bool => (int) ($variant->id ?? 0) === $requestedVariantId);

                if (
                    ! $requestedVariant instanceof ProductVariant
                    || (string) ($requestedVariant->status ?? 'active') !== 'active'
                    || (
                        $requestedVariant->relationLoaded('productColor')
                        && $requestedVariant->productColor
                        && (string) ($requestedVariant->productColor->status ?? 'active') !== 'active'
                    )
                ) {
                    throw ValidationException::withMessages([
                        'cart' => __('front.checkout.cart_changed'),
                    ]);
                }

                if (
                    is_numeric($requestedVariant->quantity)
                    && (int) $requestedVariant->quantity < $quantity
                ) {
                    throw ValidationException::withMessages([
                        'cart' => __('front.checkout.stock_changed', [
                            'product' => $storedItem['title'] ?? __('front.cart.product'),
                        ]),
                    ]);
                }
            }

            $rebuiltItem = $this->buildItem($product, $storedItem);

            if (
                $requestedVariantId > 0
                && (int) ($rebuiltItem['variant_id'] ?? 0) !== $requestedVariantId
            ) {
                throw ValidationException::withMessages([
                    'cart' => __('front.checkout.cart_changed'),
                ]);
            }

            $rebuiltItem['qty'] = $quantity;
            $refreshedItems[$rebuiltItem['key']] = $rebuiltItem;
        }

        session()->put(self::SESSION_KEY, $refreshedItems);

        return $this->state();
    }

    public function add(Product $product, array $input = []): array
    {
        $items = $this->storedItems();
        $item = $this->buildItem($product, $input);
        $qty = max(1, (int) ($input['quantity'] ?? $input['qty'] ?? 1));

        if (isset($items[$item['key']])) {
            $items[$item['key']]['qty'] = max(1, (int) ($items[$item['key']]['qty'] ?? 1)) + $qty;
        } else {
            $item['qty'] = $qty;
            $items[$item['key']] = $item;
        }

        session()->put(self::SESSION_KEY, $items);

        return $this->state();
    }

    public function update(string $key, int $qty): array
    {
        $items = $this->storedItems();

        if ($qty <= 0) {
            unset($items[$key]);
            session()->put(self::SESSION_KEY, $items);

            return $this->state();
        }

        if (isset($items[$key])) {
            $items[$key]['qty'] = $qty;
            session()->put(self::SESSION_KEY, $items);
        }

        return $this->state();
    }

    public function remove(string $key): array
    {
        $items = $this->storedItems();
        unset($items[$key]);
        session()->put(self::SESSION_KEY, $items);

        return $this->state();
    }

    public function clear(): array
    {
        session()->forget(self::SESSION_KEY);

        return $this->state();
    }

    protected function storedItems(): array
    {
        $items = session()->get(self::SESSION_KEY, []);

        return is_array($items) ? $items : [];
    }

    protected function buildItem(Product $product, array $input): array
    {
        $locale = app()->getLocale();
        $product->loadMissing(['variants.size', 'variants.productColor', 'productColors.filterColor']);
        $title = $locale === 'ar'
            ? ($product->title_ar ?: $product->title_en ?: $product->model_no ?: __('front.brand'))
            : ($product->title_en ?: $product->title_ar ?: $product->model_no ?: __('front.brand'));
        $currency = $locale === 'ar' ? ($product->currency_ar ?: 'SYP') : ($product->currency_en ?: 'SYP');
        $basePrice = (int) round((float) $product->price);
        $baseCompare = (int) round((float) ($product->compare_price ?? 0));

        $mainImagePath = $this->imageCatalog->mainImagePath($product);
        $mainImageUrl = filled($mainImagePath)
            ? Storage::disk('public')->url($mainImagePath)
            : asset('images/products/4brouwn1.jpg');

        $colors = $this->imageCatalog->availableColors($product);
        $selectedColor = $this->resolveColor($colors, $input);
        $selectedProductColor = $this->resolveProductColorModel($product, $selectedColor);
        $colorSwatchImage = $this->swatchImageUrl($selectedProductColor?->swatch_image ?? null);
        $colorHex = $this->normalizeHexColor($selectedProductColor?->color_hex ?? null)
            ?? $this->normalizeHexColor($selectedProductColor?->filterColor?->hex ?? null);
        $colorSwatchStyle = $this->swatchStyle($colorSwatchImage, $colorHex);
        $selectedVariant = $this->resolveVariant($product, $input, $selectedColor);
        $selectedSize = $this->variantSizeLabel($selectedVariant, $locale) ?: $this->resolveSize($product, (string) ($input['size'] ?? ''));
        $unitPrice = $this->resolveVariantNumber($selectedVariant?->price, $basePrice) ?? $basePrice;
        $comparePrice = $this->resolveVariantNumber($selectedVariant?->compare_price, $baseCompare) ?? null;
        $gallery = collect(array_merge([$mainImageUrl], array_map(fn (array $color): string => $color['primary_thumb_url'] ?? '', $colors)))
            ->filter()
            ->unique()
            ->take(4)
            ->values()
            ->all();
        $selectedColorLabel = (string) ($selectedColor['name'] ?? $selectedColor['class_name'] ?? '');
        $selectedColorCode = (string) ($selectedColor['color_code'] ?? '');
        $selectedVariantId = (int) ($selectedVariant?->id ?? 0);
        $selectedSizeId = (int) ($selectedVariant?->size_id ?? ($input['size_id'] ?? 0));
        $selectedSizeCode = (string) ($selectedVariant?->size?->code ?? ($input['size_code'] ?? ''));
        $unitPriceLabel = number_format($unitPrice, 0) . ' ' . $currency;
        $comparePriceLabel = $comparePrice !== null && $comparePrice > $unitPrice
            ? number_format($comparePrice, 0) . ' ' . $currency
            : null;

        $colorKey = $selectedColor['id'] ?? null;
        if (! filled($colorKey)) {
            $colorKey = $selectedColorCode !== '' ? $selectedColorCode : $selectedColorLabel;
        }

        $key = $this->cartKey((int) $product->getKey(), $selectedVariantId > 0 ? $selectedVariantId : null, $selectedSize, (string) $colorKey);

        return [
            'key' => $key,
            'product_id' => $product->getKey(),
            'slug' => $product->slug ?? null,
            'title' => $title,
            'description' => $locale === 'ar'
                ? ($product->description_ar ?: $product->description_en ?: '')
                : ($product->description_en ?: $product->description_ar ?: ''),
            'image' => $selectedColor['primary_thumb_url'] ?? $mainImageUrl,
            'gallery' => $gallery,
            'url' => filled($product->slug) ? route('front.products.show', $product->slug) : route('front.products.show', 'placeholder-product'),
            'variant_id' => $selectedVariantId > 0 ? $selectedVariantId : null,
            'color_id' => (int) ($selectedColor['id'] ?? 0) ?: null,
            'color_code' => $selectedColorCode ?: null,
            'size' => $selectedSize,
            'size_id' => $selectedSizeId > 0 ? $selectedSizeId : null,
            'size_code' => $selectedSizeCode ?: null,
            'color_name' => $selectedColorLabel,
            'color_class' => $selectedColor['class_name'] ?? '',
            'color_hex' => $colorHex,
            'color_swatch_image' => $colorSwatchImage,
            'color_swatch_style' => $colorSwatchStyle,
            'meta_variant' => implode(' / ', array_filter([$selectedColorLabel, $selectedSize])),
            'qty' => 1,
            'unit_price' => $unitPrice,
            'unit_price_label' => $unitPriceLabel,
            'compare_price' => $comparePrice,
            'compare_price_label' => $comparePriceLabel,
            'base_price' => $unitPrice,
            'base_currency' => $currency,
            'price_label' => $unitPriceLabel,
            'update_url' => route('front.cart.update', $key),
            'remove_url' => route('front.cart.remove', $key),
        ];
    }

    protected function resolveColor(array $colors, array $input): array
    {
        $needleId = (int) ($input['color_id'] ?? 0);
        $needleCode = trim((string) ($input['color_code'] ?? ''));
        $needleName = trim((string) ($input['color_name'] ?? $input['color'] ?? ''));

        if ($needleId > 0) {
            foreach ($colors as $color) {
                if ((int) ($color['id'] ?? 0) === $needleId) {
                    return $color;
                }
            }
        }

        if ($needleCode !== '') {
            foreach ($colors as $color) {
                if (strcasecmp((string) ($color['color_code'] ?? ''), $needleCode) === 0) {
                    return $color;
                }
            }
        }

        if ($needleName !== '') {
            foreach ($colors as $color) {
                if (
                    strcasecmp((string) ($color['name'] ?? ''), $needleName) === 0 ||
                    strcasecmp((string) ($color['class_name'] ?? ''), $needleName) === 0
                ) {
                    return $color;
                }
            }
        }

        return $colors[0] ?? [];
    }

    protected function resolveVariant(Product $product, array $input, array $selectedColor): ?ProductVariant
    {
        $variants = $product->relationLoaded('variants')
            ? $product->getRelation('variants')
            : $product->variants()->with(['size', 'productColor'])->get();

        $variantId = (int) ($input['variant_id'] ?? 0);

        if ($variantId > 0) {
            $variant = $variants->first(fn ($item): bool => (int) ($item->id ?? 0) === $variantId);
            if ($variant instanceof ProductVariant) {
                return $variant;
            }
        }

        $colorId = (int) ($input['color_id'] ?? ($selectedColor['id'] ?? 0));
        $sizeId = (int) ($input['size_id'] ?? 0);
        $sizeCode = trim((string) ($input['size_code'] ?? ''));
        $sizeName = trim((string) ($input['size'] ?? ''));

        $matching = $variants->filter(function ($variant) use ($colorId, $sizeId, $sizeCode, $sizeName): bool {
            if ($colorId > 0 && (int) ($variant->product_color_id ?? 0) !== $colorId) {
                return false;
            }

            if ($sizeId > 0 && (int) ($variant->size_id ?? 0) === $sizeId) {
                return true;
            }

            if ($sizeCode !== '' && $variant->relationLoaded('size') && $variant->size && strcasecmp((string) ($variant->size->code ?? ''), $sizeCode) === 0) {
                return true;
            }

            if ($sizeName !== '' && $variant->relationLoaded('size') && $variant->size) {
                $variantSize = app()->getLocale() === 'ar'
                    ? ($variant->size->name_ar ?: $variant->size->name_en ?: $variant->size->code)
                    : ($variant->size->name_en ?: $variant->size->name_ar ?: $variant->size->code);

                return strcasecmp((string) $variantSize, $sizeName) === 0;
            }

            return $sizeId <= 0 && $sizeCode === '' && $sizeName === '';
        });

        if ($matching->isNotEmpty()) {
            $defaultVariant = $matching->first(fn ($item): bool => (bool) ($item->is_default ?? false));

            return $defaultVariant instanceof ProductVariant ? $defaultVariant : ($matching->first() instanceof ProductVariant ? $matching->first() : null);
        }

        if ($colorId > 0) {
            $colorMatching = $variants->filter(fn ($variant): bool => (int) ($variant->product_color_id ?? 0) === $colorId);
            if ($colorMatching->isNotEmpty()) {
                $defaultVariant = $colorMatching->first(fn ($item): bool => (bool) ($item->is_default ?? false));

                return $defaultVariant instanceof ProductVariant ? $defaultVariant : ($colorMatching->first() instanceof ProductVariant ? $colorMatching->first() : null);
            }
        }

        $defaultVariant = $variants->first(fn ($item): bool => (bool) ($item->is_default ?? false));

        return $defaultVariant instanceof ProductVariant ? $defaultVariant : ($variants->first() instanceof ProductVariant ? $variants->first() : null);
    }

    protected function variantSizeLabel(?ProductVariant $variant, string $locale): ?string
    {
        if (! $variant instanceof ProductVariant || ! $variant->relationLoaded('size') || ! $variant->size) {
            return null;
        }

        return $locale === 'ar'
            ? ($variant->size->name_ar ?: $variant->size->name_en ?: $variant->size->code)
            : ($variant->size->name_en ?: $variant->size->name_ar ?: $variant->size->code);
    }

    protected function resolveVariantNumber(mixed $value, mixed $fallback): ?int
    {
        $current = is_numeric($value) ? (float) $value : (is_numeric($fallback) ? (float) $fallback : null);

        if ($current === null || $current <= 0) {
            return null;
        }

        return (int) round($current);
    }

    protected function resolveSize(Product $product, string $needle): string
    {
        $sizes = $product->variants
            ->filter(fn ($variant) => filled($variant->size))
            ->map(fn ($variant) => app()->getLocale() === 'ar'
                ? ($variant->size->name_ar ?: $variant->size->name_en ?: $variant->size->code)
                : ($variant->size->name_en ?: $variant->size->name_ar ?: $variant->size->code))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($needle !== '' && in_array($needle, $sizes, true)) {
            return $needle;
        }

        return $sizes[0] ?? 'S';
    }

    protected function resolveProductColorModel(Product $product, array $selectedColor): ?ProductColor
    {
        $colorId = (int) ($selectedColor['id'] ?? 0);

        if ($colorId <= 0) {
            return null;
        }

        $colors = $product->relationLoaded('productColors')
            ? $product->getRelation('productColors')
            : $product->productColors()->with('filterColor')->get();

        $match = $colors->first(fn ($color): bool => (int) ($color->id ?? 0) === $colorId);

        return $match instanceof ProductColor ? $match : null;
    }

    protected function normalizeHexColor(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (! str_starts_with($value, '#')) {
            $value = '#'.$value;
        }

        return preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $value) === 1
            ? strtoupper($value)
            : null;
    }

    protected function swatchImageUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (preg_match('~^(https?:)?//~i', $path) || str_starts_with($path, 'data:')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }

    protected function swatchStyle(?string $swatchImage, ?string $hex): string
    {
        if (filled($swatchImage)) {
            return sprintf(
                "background-image: url('%s'); background-size: cover; background-position: center; background-color: transparent;",
                htmlspecialchars((string) $swatchImage, ENT_QUOTES, 'UTF-8')
            );
        }

        if (filled($hex)) {
            return 'background-color: '.htmlspecialchars((string) $hex, ENT_QUOTES, 'UTF-8').';';
        }

        return '';
    }

    protected function cartKey(int $productId, ?int $variantId, string $size, string $color): string
    {
        return sha1($productId . '|' . ($variantId ?? 0) . '|' . $size . '|' . $color);
    }

    protected function normalizeItem(array $item): array
    {
        $item['qty'] = max(1, (int) ($item['qty'] ?? 1));
        $item['unit_price'] = (int) ($item['unit_price'] ?? $item['base_price'] ?? 0);
        $item['base_price'] = $item['unit_price'];
        $item['base_currency'] = $item['base_currency'] ?? 'SYP';
        $item['unit_price_label'] = $item['unit_price_label'] ?? (number_format($item['unit_price'], 0) . ' ' . $item['base_currency']);
        $item['price_label'] = $item['price_label'] ?? $item['unit_price_label'];
        $item['compare_price'] = isset($item['compare_price']) ? (int) $item['compare_price'] : null;
        $item['compare_price_label'] = $item['compare_price_label'] ?? ($item['compare_price'] ? number_format((int) $item['compare_price'], 0) . ' ' . $item['base_currency'] : null);
        $item['line_total'] = $item['unit_price'] * $item['qty'];
        $item['line_total_label'] = number_format($item['line_total'], 0) . ' ' . $item['base_currency'];
        $item['color_hex'] = $item['color_hex'] ?? null;
        $item['color_swatch_image'] = $item['color_swatch_image'] ?? null;
        $item['color_swatch_style'] = $item['color_swatch_style'] ?? '';
        $item['meta_variant'] = $item['meta_variant'] ?? implode(' / ', array_filter([$item['color_name'] ?? '', $item['size'] ?? '']));
        $item['update_url'] = $item['update_url'] ?? route('front.cart.update', $item['key']);
        $item['remove_url'] = $item['remove_url'] ?? route('front.cart.remove', $item['key']);

        return $item;
    }
}
