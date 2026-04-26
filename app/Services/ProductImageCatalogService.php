<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductColor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ProductImageCatalogService
{
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

        $result = [];

        foreach ($this->colorsForProduct($product) as $color) {
            if (! $this->isActiveColor($color)) {
                continue;
            }

            $imageSet = $this->imageSetForColor($product, $color);

            if ($imageSet['source_paths'] === []) {
                continue;
            }

            $result[] = [
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

        return $this->availableColorsCache[$productId] = $result;
    }

    public function mainImagePath(Product $product): ?string
    {
        $productId = (int) $product->getKey();

        if (array_key_exists($productId, $this->mainImagePathCache)) {
            return $this->mainImagePathCache[$productId];
        }

        $color = $this->colorsForProduct($product)
            ->first(fn (ProductColor $productColor): bool => ($productColor->status ?? 'inactive') === 'active')
            ?? $this->colorsForProduct($product)->first();

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

        $urls = array_map(
            fn (string $sourcePath): string => Storage::disk('public')->url($sourcePath),
            $sourcePaths,
        );

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
        $productCode = trim((string) ($product->model_no ?? ''));
        $colorCode = trim((string) ($color->color_code ?? ''));

        if ($productCode === '' || $colorCode === '') {
            return [];
        }

        $baseName = $productCode . '-' . $colorCode;

        return $this->sourcePathsIndex()[$baseName] ?? [];
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
            usort($paths, function (string $left, string $right) use ($baseName): int {
                $leftName = pathinfo($left, PATHINFO_FILENAME);
                $rightName = pathinfo($right, PATHINFO_FILENAME);

                if ($leftName === $baseName && $rightName !== $baseName) {
                    return -1;
                }

                if ($rightName === $baseName && $leftName !== $baseName) {
                    return 1;
                }

                $leftSuffix = (int) preg_replace('/^' . preg_quote($baseName, '/') . '-/', '', $leftName);
                $rightSuffix = (int) preg_replace('/^' . preg_quote($baseName, '/') . '-/', '', $rightName);

                return $leftSuffix <=> $rightSuffix ?: $leftName <=> $rightName;
            });

            $index[$baseName] = array_values($paths);
        }

        return $this->sourcePathsIndexCache = $index;
    }

    protected function isProductImageSource(string $path): bool
    {
        $normalizedPath = ltrim($path, '/');

        if (pathinfo($normalizedPath, PATHINFO_DIRNAME) !== $this->sourceDirectory()) {
            return false;
        }

        return in_array(strtolower((string) pathinfo($normalizedPath, PATHINFO_EXTENSION)), [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'webp',
        ], true);
    }

    protected function normalizeImageBaseName(string $fileName): string
    {
        return (string) preg_replace('/-\d+$/', '', trim($fileName));
    }

    protected function normalizeColorClass(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'four-Black';
        }

        $value = preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?: $value;
        $value = trim($value, '-');

        return $value !== '' ? 'four-' . $value : 'four-Black';
    }
}
