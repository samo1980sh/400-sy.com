<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class FrontWishlistService
{
    protected const SESSION_KEY = 'frontend_wishlist';

    public function ids(): array
    {
        $ids = session(self::SESSION_KEY, []);

        if (! is_array($ids)) {
            $ids = [];
        }

        return collect($ids)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function count(): int
    {
        return count($this->ids());
    }

    public function has(Product|int $product): bool
    {
        $id = $product instanceof Product ? (int) $product->getKey() : (int) $product;

        return $id > 0 && in_array($id, $this->ids(), true);
    }

    public function add(Product $product): array
    {
        $ids = $this->ids();
        $id = (int) $product->getKey();

        if ($id > 0 && ! in_array($id, $ids, true)) {
            $ids[] = $id;
        }

        return $this->store($ids);
    }

    public function remove(Product|int $product): array
    {
        $id = $product instanceof Product ? (int) $product->getKey() : (int) $product;
        $ids = collect($this->ids())
            ->reject(fn (int $itemId): bool => $itemId === $id)
            ->values()
            ->all();

        return $this->store($ids);
    }

    public function state(): array
    {
        $ids = $this->ids();

        return [
            'ids' => $ids,
            'count' => count($ids),
            'url' => route('front.wishlist.index'),
        ];
    }

    public function cleanupVisibleIds(): array
    {
        $ids = $this->ids();

        if ($ids === []) {
            return $this->state();
        }

        $visibleIds = Product::query()
            ->visibleToFrontendVisitor()
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return $this->store($visibleIds);
    }

    protected function store(array $ids): array
    {
        $ids = collect($ids)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        session([self::SESSION_KEY => $ids]);

        return [
            'ids' => $ids,
            'count' => count($ids),
            'url' => route('front.wishlist.index'),
        ];
    }
}
