<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FrontWishlistService
{
    protected const SESSION_KEY = 'frontend_wishlist';

    public function ids(): array
    {
        $customer = $this->customer();

        if ($customer instanceof Customer) {
            return DB::table('customer_wishlist_items')
                ->where('customer_id', $customer->getKey())
                ->orderBy('id')
                ->pluck('product_id')
                ->map(fn ($id): int => (int) $id)
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values()
                ->all();
        }

        $ids = session(self::SESSION_KEY, []);

        if (! is_array($ids)) {
            $ids = [];
        }

        return $this->normalizeIds($ids);
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

    public function mergeSessionIntoCustomer(Customer $customer): array
    {
        $sessionIds = $this->sessionIds();

        if ($sessionIds !== []) {
            $now = now();
            $rows = collect($sessionIds)
                ->map(fn (int $productId): array => [
                    'customer_id' => (int) $customer->getKey(),
                    'product_id' => $productId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all();

            DB::table('customer_wishlist_items')->insertOrIgnore($rows);
        }

        $ids = DB::table('customer_wishlist_items')
            ->where('customer_id', $customer->getKey())
            ->orderBy('id')
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        session([self::SESSION_KEY => $this->normalizeIds($ids)]);

        return $this->cleanupVisibleIds();
    }

    public function copyCustomerWishlistToSession(Customer $customer): void
    {
        $ids = DB::table('customer_wishlist_items')
            ->where('customer_id', $customer->getKey())
            ->orderBy('id')
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        session([self::SESSION_KEY => $this->normalizeIds($ids)]);
    }

    protected function store(array $ids): array
    {
        $ids = $this->normalizeIds($ids);
        $customer = $this->customer();

        if ($customer instanceof Customer) {
            DB::transaction(function () use ($customer, $ids): void {
                DB::table('customer_wishlist_items')
                    ->where('customer_id', $customer->getKey())
                    ->delete();

                if ($ids === []) {
                    return;
                }

                $now = now();
                DB::table('customer_wishlist_items')->insert(
                    collect($ids)
                        ->map(fn (int $productId): array => [
                            'customer_id' => (int) $customer->getKey(),
                            'product_id' => $productId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                        ->all()
                );
            });
        }

        session([self::SESSION_KEY => $ids]);

        return [
            'ids' => $ids,
            'count' => count($ids),
            'url' => route('front.wishlist.index'),
        ];
    }

    protected function sessionIds(): array
    {
        $ids = session(self::SESSION_KEY, []);

        return is_array($ids) ? $this->normalizeIds($ids) : [];
    }

    protected function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    protected function customer(): ?Customer
    {
        $customer = Auth::guard('customer')->user();

        return $customer instanceof Customer ? $customer : null;
    }
}
