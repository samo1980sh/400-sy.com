<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ProductImageCatalogService
{
    protected const FALLBACK_COLOR_CLASS = 'four-Black';

    protected const IMAGE_EXTENSIONS = [
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
    ];

    protected ?array $sourceFilesCache = null;
    protected ?array $sourcePathsIndexCache = null;
    protected array $mainImagePathCache = [];
    protected array $availableColorsCache = [];

    public function sourceDirectory(): string
    {
        return trim((string) config('product_images.source_directory', 'products'), '/');
    }

    public function sourceFiles(): array
    {
        if ($this->sourceFilesCache !== null) {
            return $this->sourceFilesCache;
        }

        return $this->sourceFilesCache = Storage::disk('public')->files($this->sourceDirectory());
    }

    public function availableColors(Product $product): array
    {
        $productId = (int) $product->getKey();

        if (isset($this->availableColorsCache[$productId])) {
            return $this->availableColorsCache[$productId];
        }

        $colors = [];

        foreach ($this->colorsForProduct($product) as $color) {
            if (! $this->isActiveColor($color)) {
                continue;
            }

            $imageSet = $this->imageSetForColor($product, $color);

            if ($imageSet['source_paths'] === []) {
                continue;
            }

            $colors[] = $this->buildColorPayload($color, $imageSet);
        }

        return $this->availableColorsCache[$productId] = $colors;
    }

    public function mainImagePath(Product $product): ?string
    {
        $productId = (int) $product->getKey();

        if (array_key_exists($productId, $this->mainImagePathCache)) {
            return $this->mainImagePathCache[$productId];
        }

        $color = $this->primaryColorForProduct($product);

        if (! $color instanceof ProductColor) {
            return $this->mainImagePathCache[$productId] = null;
        }

        $imageSet = $this->imageSetForColor($product, $color);

        return $this->mainImagePathCache[$productId] = $imageSet['source_paths'][0] ?? null;
    }

    public function imageSetForColor(Product $product, ProductColor $color): array
    {
        $sourcePaths = $this->sourcePathsForColor($product, $color);

        if ($sourcePaths === []) {
            return $this->emptyImageSet();
        }

        $urls = $this->urlsForSourcePaths($sourcePaths);

        return [
            'source_paths' => $sourcePaths,
            'thumb_urls' => $urls,
            'card_urls' => $urls,
            'detail_urls' => $urls,
            'zoom_urls' => $urls,
            'primary_thumb_url' => $urls[0] ?? null,
            'primary_zoom_url' => $urls[0] ?? null,
        ];
    }

    protected function sourcePathsForColor(Product $product, ProductColor $color): array
    {
        $baseName = $this->sourceBaseNameForColor($product, $color);

        if ($baseName === null) {
            return [];
        }

        return $this->sourcePathsIndex()[$baseName] ?? [];
    }

    protected function sourceBaseNameForColor(Product $product, ProductColor $color): ?string
    {
        $productCode = trim((string) ($product->model_no ?? ''));
        $colorCode = trim((string) ($color->color_code ?? ''));

        if ($productCode === '' || $colorCode === '') {
            return null;
        }

        return $productCode . '-' . $colorCode;
    }

    protected function buildColorPayload(ProductColor $color, array $imageSet): array
    {
        return [
            'id' => $color->id,
            'name' => trim((string) ($color->color_name_ar ?: $color->color_name_en ?: '-')),
            'name_ar' => trim((string) ($color->color_name_ar ?: '')),
            'name_en' => trim((string) ($color->color_name_en ?: '')),
            'class_name' => $this->normalizeColorClass((string) ($color->color_name_en ?: $color->color_name_ar ?: $color->color_code)),
            'color_code' => $color->color_code,
            'status' => $color->status ?? 'inactive',
            'sort_order' => (int) ($color->sort_order ?? 0),
            'count' => count($imageSet['source_paths']),
            'source_paths' => $imageSet['source_paths'],
            'thumb_urls' => $imageSet['thumb_urls'],
            'card_urls' => $imageSet['card_urls'],
            'detail_urls' => $imageSet['detail_urls'],
            'zoom_urls' => $imageSet['zoom_urls'],
            'primary_thumb_url' => $imageSet['primary_thumb_url'],
            'primary_zoom_url' => $imageSet['primary_zoom_url'],
        ];
    }

    protected function emptyImageSet(): array
    {
        return [
            'source_paths' => [],
            'thumb_urls' => [],
            'card_urls' => [],
            'detail_urls' => [],
            'zoom_urls' => [],
            'primary_thumb_url' => null,
            'primary_zoom_url' => null,
        ];
    }

    protected function urlsForSourcePaths(array $sourcePaths): array
    {
        return array_map(
            fn (string $sourcePath): string => Storage::disk('public')->url($sourcePath),
            $sourcePaths,
        );
    }

    protected function primaryColorForProduct(Product $product): ?ProductColor
    {
        $colors = $this->colorsForProduct($product);

        return $colors->first(fn (ProductColor $productColor): bool => $this->isActiveColor($productColor))
            ?? $colors->first();
    }

    /**
     * @return Collection<int, ProductColor>
     */
    protected function colorsForProduct(Product $product): Collection
    {
        if ($product->relationLoaded('productColors')) {
            $colors = $product->getRelation('productColors');

            if ($colors instanceof Collection) {
                return $colors->sortBy([
                    ['sort_order', 'asc'],
                    ['id', 'asc'],
                ])->values();
            }
        }

        return $product->productColors()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    protected function isActiveColor(ProductColor $color): bool
    {
        return strtolower(trim((string) ($color->status ?? ''))) === 'active';
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function sourcePathsIndex(): array
    {
        if ($this->sourcePathsIndexCache !== null) {
            return $this->sourcePathsIndexCache;
        }

        $index = [];

        foreach ($this->sourceFiles() as $sourcePath) {
            if (! $this->isProductImageSource($sourcePath)) {
                continue;
            }

            $baseName = $this->normalizeImageBaseName(pathinfo($sourcePath, PATHINFO_FILENAME));
            $index[$baseName] ??= [];
            $index[$baseName][] = $sourcePath;
        }

        foreach ($index as $baseName => $paths) {
            usort(
                $paths,
                fn (string $left, string $right): int => $this->compareSourcePaths($baseName, $left, $right),
            );

            $index[$baseName] = array_values($paths);
        }

        return $this->sourcePathsIndexCache = $index;
    }

    protected function compareSourcePaths(string $baseName, string $left, string $right): int
    {
        $leftName = pathinfo($left, PATHINFO_FILENAME);
        $rightName = pathinfo($right, PATHINFO_FILENAME);

        if ($leftName === $baseName && $rightName !== $baseName) {
            return -1;
        }

        if ($rightName === $baseName && $leftName !== $baseName) {
            return 1;
        }

        $leftSuffix = $this->imageSuffixNumber($baseName, $leftName);
        $rightSuffix = $this->imageSuffixNumber($baseName, $rightName);

        return $leftSuffix <=> $rightSuffix ?: $leftName <=> $rightName;
    }

    protected function imageSuffixNumber(string $baseName, string $fileName): int
    {
        return (int) preg_replace('/^' . preg_quote($baseName, '/') . '-/', '', $fileName);
    }

    protected function isProductImageSource(string $path): bool
    {
        $normalizedPath = ltrim($path, '/');

        if (pathinfo($normalizedPath, PATHINFO_DIRNAME) !== $this->sourceDirectory()) {
            return false;
        }

        return in_array(
            strtolower((string) pathinfo($normalizedPath, PATHINFO_EXTENSION)),
            self::IMAGE_EXTENSIONS,
            true,
        );
    }

    protected function normalizeImageBaseName(string $fileName): string
    {
        return (string) preg_replace('/-\d+$/', '', trim($fileName));
    }

    protected function normalizeColorClass(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return self::FALLBACK_COLOR_CLASS;
        }

        $value = preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?: $value;
        $value = trim($value, '-');

        return $value !== '' ? 'four-' . $value : self::FALLBACK_COLOR_CLASS;
    }
}
