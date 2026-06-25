<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductWholesaleAvailability;
use App\Models\ProductWholesaleQuantity;
use App\Models\Trader;
use App\Models\TraderOrder;
use App\Models\TraderOrderItem;
use App\Models\TraderOrderStatusHistory;
use App\Services\FrontHomePageDataService;
use App\Services\ProductPresentationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FrontTraderOrderController extends Controller
{
    public function __construct(
        protected FrontHomePageDataService $homePageData,
        protected ProductPresentationService $productPresentation,
    ) {}

    public function ordersPage(Request $request): View
    {
        /** @var Trader $trader */
        $trader = Auth::guard('trader')->user();
        $trader->loadMissing('wholesaleCustomerGroup');

        $isArabic = app()->getLocale() === 'ar';
        $filters = [
            'status' => (string) $request->query('status', ''),
            'payment_status' => (string) $request->query('payment_status', ''),
        ];

        $orders = $trader->orders()
            ->withCount('items')
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['payment_status'] !== '', fn ($query) => $query->where('payment_status', $filters['payment_status']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('frontend.pages.trader.orders', array_merge($this->homePageData->build(), [
            'page_title' => $isArabic ? 'طلباتي' : 'My Orders',
            'page_subtitle' => $isArabic
                ? 'متابعة طلبات الجملة وحالاتها بعد الإرسال.'
                : 'Track submitted wholesale orders and their statuses.',
            'breadcrumb_items' => [
                ['label' => $isArabic ? 'لوحة التاجر' : 'Trader Dashboard', 'url' => route('front.trader.dashboard')],
                ['label' => $isArabic ? 'طلباتي' : 'My Orders', 'url' => null],
            ],
            'trader' => $trader,
            'orders' => $orders,
            'filters' => $filters,
            'trader_cart_count' => count($this->cart()),
        ]));
    }

    public function showOrder(TraderOrder $traderOrder): View
    {
        /** @var Trader $trader */
        $trader = Auth::guard('trader')->user();
        abort_unless((int) $traderOrder->trader_id === (int) $trader->getKey(), 404);

        $isArabic = app()->getLocale() === 'ar';
        $traderOrder->load(['items.wholesaleColor', 'statusHistory.changedBy']);

        return view('frontend.pages.trader.order-show', array_merge($this->homePageData->build(), [
            'page_title' => ($isArabic ? 'تفاصيل الطلب ' : 'Order Details ').$traderOrder->order_no,
            'page_subtitle' => $isArabic
                ? 'تفاصيل بنود طلب الجملة وسجل الحالة.'
                : 'Wholesale order items and status history.',
            'breadcrumb_items' => [
                ['label' => $isArabic ? 'لوحة التاجر' : 'Trader Dashboard', 'url' => route('front.trader.dashboard')],
                ['label' => $isArabic ? 'طلباتي' : 'My Orders', 'url' => route('front.trader.orders.index')],
                ['label' => $traderOrder->order_no, 'url' => null],
            ],
            'trader' => $trader,
            'order' => $traderOrder,
            'trader_cart_count' => count($this->cart()),
        ]));
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $isArabic = app()->getLocale() === 'ar';

        $validated = $request->validate([
            'product_wholesale_color_id' => ['required', 'integer', 'min:1'],
            'series_group' => ['required', 'integer', 'min:1'],
            'series_count' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], $this->validationMessages($isArabic));

        /** @var Trader $trader */
        $trader = Auth::guard('trader')->user();
        $trader->loadMissing('wholesaleCustomerGroup');

        $cartItem = $this->buildCartItem(
            trader: $trader,
            product: $product,
            colorId: (int) $validated['product_wholesale_color_id'],
            seriesGroup: (int) $validated['series_group'],
            seriesCount: (int) $validated['series_count'],
            notes: trim((string) ($validated['notes'] ?? '')),
            currentCart: $this->cart(),
            exceptCartKey: null,
        );

        $cart = $this->cart();
        $cartKey = $cartItem['cart_key'];

        if (isset($cart[$cartKey])) {
            $cartItem['series_count'] = (int) $cart[$cartKey]['series_count'] + (int) $cartItem['series_count'];
            $cartItem = $this->buildCartItem(
                trader: $trader,
                product: $product,
                colorId: (int) $cartItem['product_wholesale_color_id'],
                seriesGroup: (int) $cartItem['series_group'],
                seriesCount: (int) $cartItem['series_count'],
                notes: $cartItem['notes'],
                currentCart: $cart,
                exceptCartKey: $cartKey,
            );
        }

        $cart[$cartKey] = $cartItem;
        session()->put($this->cartSessionKey($trader), $cart);

        return back()->with(
            'success',
            $isArabic
                ? 'تمت إضافة السيرية إلى طلب الجملة المؤقت. يمكنك إضافة منتجات أخرى ثم إرسال الطلب من صفحة طلب الجملة.'
                : 'The series was added to your wholesale cart. You can add more products and submit the order from the wholesale cart.'
        );
    }

    public function cartPage(): View
    {
        /** @var Trader $trader */
        $trader = Auth::guard('trader')->user();
        $trader->loadMissing('wholesaleCustomerGroup');

        $isArabic = app()->getLocale() === 'ar';
        [$cart, $refreshWarning] = $this->refreshedCart($trader);
        $summary = $this->cartSummary($cart);

        return view('frontend.pages.trader.cart', array_merge($this->homePageData->build(), [
            'page_title' => $isArabic ? 'طلب الجملة المؤقت' : 'Wholesale Cart',
            'page_subtitle' => $isArabic
                ? 'راجع السيريات التي أضفتها قبل إرسال طلب الجملة إلى الإدارة.'
                : 'Review selected wholesale series before submitting your order.',
            'breadcrumb_items' => [
                ['label' => $isArabic ? 'لوحة التاجر' : 'Trader Dashboard', 'url' => route('front.trader.dashboard')],
                ['label' => $isArabic ? 'منتجات الجملة' : 'Wholesale Products', 'url' => route('front.trader.products.index')],
                ['label' => $isArabic ? 'طلب الجملة المؤقت' : 'Wholesale Cart', 'url' => null],
            ],
            'trader' => $trader,
            'cart_items' => $cart,
            'cart_summary' => $summary,
            'cart_warning' => $refreshWarning,
            'trader_cart_count' => count($cart),
        ]));
    }

    public function updateCartItem(Request $request, string $cartKey): RedirectResponse
    {
        $isArabic = app()->getLocale() === 'ar';

        $validated = $request->validate([
            'series_count' => ['required', 'integer', 'min:1'],
        ], $this->validationMessages($isArabic));

        /** @var Trader $trader */
        $trader = Auth::guard('trader')->user();
        $trader->loadMissing('wholesaleCustomerGroup');

        $cart = $this->cart();

        if (! isset($cart[$cartKey])) {
            return back()->withErrors(['cart' => $isArabic ? 'البند غير موجود في طلب الجملة المؤقت.' : 'Cart item was not found.']);
        }

        $oldItem = $cart[$cartKey];
        $product = Product::query()->findOrFail((int) $oldItem['product_id']);

        $cart[$cartKey] = $this->buildCartItem(
            trader: $trader,
            product: $product,
            colorId: (int) $oldItem['product_wholesale_color_id'],
            seriesGroup: (int) $oldItem['series_group'],
            seriesCount: (int) $validated['series_count'],
            notes: (string) ($oldItem['notes'] ?? ''),
            currentCart: $cart,
            exceptCartKey: $cartKey,
        );

        session()->put($this->cartSessionKey($trader), $cart);

        return back()->with('success', $isArabic ? 'تم تحديث عدد السيريات.' : 'Series count updated.');
    }

    public function removeCartItem(string $cartKey): RedirectResponse
    {
        /** @var Trader $trader */
        $trader = Auth::guard('trader')->user();

        $cart = $this->cart();
        unset($cart[$cartKey]);
        session()->put($this->cartSessionKey($trader), $cart);

        return back()->with('success', app()->getLocale() === 'ar' ? 'تم حذف البند من طلب الجملة المؤقت.' : 'Item removed from wholesale cart.');
    }

    public function submitCart(Request $request): RedirectResponse
    {
        $isArabic = app()->getLocale() === 'ar';

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ], $this->validationMessages($isArabic));

        /** @var Trader $trader */
        $trader = Auth::guard('trader')->user();
        $trader->loadMissing('wholesaleCustomerGroup');

        $cart = $this->cart();

        if ($cart === []) {
            return back()->withErrors(['cart' => $isArabic ? 'طلب الجملة المؤقت فارغ.' : 'Wholesale cart is empty.']);
        }

        $preparedItems = [];
        foreach ($cart as $cartKey => $item) {
            $product = Product::query()->findOrFail((int) $item['product_id']);

            $preparedItems[$cartKey] = $this->buildCartItem(
                trader: $trader,
                product: $product,
                colorId: (int) $item['product_wholesale_color_id'],
                seriesGroup: (int) $item['series_group'],
                seriesCount: (int) $item['series_count'],
                notes: (string) ($item['notes'] ?? ''),
                currentCart: $cart,
                exceptCartKey: $cartKey,
            );
        }

        $groupName = $this->groupName($trader);

        $order = DB::transaction(function () use ($trader, $preparedItems, $groupName, $validated): TraderOrder {
            $order = TraderOrder::create([
                'trader_id' => $trader->getKey(),
                'trader_name_snapshot' => $trader->name,
                'trader_mobile_snapshot' => $trader->mobile,
                'trader_account_no_snapshot' => $trader->account_no,
                'trader_group_snapshot' => $groupName,
                'shipping_contact_name_snapshot' => $trader->name,
                'shipping_mobile_snapshot' => $trader->mobile,
                'shipping_city_snapshot' => $trader->city,
                'shipping_area_snapshot' => $trader->area,
                'shipping_address_line_snapshot' => $trader->address_line,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => 'cash_on_delivery',
                'total_before_discount' => 0,
                'discount_value' => 0,
                'shipping_cost' => 0,
                'total' => 0,
                'notes' => trim((string) ($validated['notes'] ?? '')),
            ]);

            $orderTotal = 0;

            foreach ($preparedItems as $item) {
                foreach ($item['series_rows'] as $row) {
                    $itemQuantity = max(0, (int) $row['quantity_per_series']) * (int) $item['series_count'];
                    $lineTotal = $itemQuantity * (float) $item['unit_price'];
                    $orderTotal += $lineTotal;

                    TraderOrderItem::create([
                        'trader_order_id' => $order->getKey(),
                        'product_id' => (int) $item['product_id'],
                        'product_wholesale_color_id' => (int) $item['product_wholesale_color_id'],
                        'series_group' => (int) $item['series_group'],
                        'size_text' => (string) $row['size_text'],
                        'product_name_snapshot' => (string) $item['product_name'],
                        'product_model_no_snapshot' => (string) $item['product_model_no'],
                        'product_sku_snapshot' => null,
                        'product_barcode_snapshot' => null,
                        'color_name_snapshot' => (string) $item['color_name'],
                        'series_snapshot' => 'Series '.(int) $item['series_group'].' x '.(int) $item['series_count'],
                        'quantity' => $itemQuantity,
                        'unit_price' => (float) $item['unit_price'],
                        'line_total' => $lineTotal,
                        'notes' => (string) ($item['notes'] ?? ''),
                    ]);
                }
            }

            $order->update([
                'total_before_discount' => $orderTotal,
                'total' => $orderTotal,
            ]);

            TraderOrderStatusHistory::create([
                'trader_order_id' => $order->getKey(),
                'from_status' => null,
                'to_status' => 'pending',
                'from_payment_status' => null,
                'to_payment_status' => 'unpaid',
                'note' => 'تم إرسال طلب الجملة من واجهة التاجر.',
                'changed_by' => null,
            ]);

            return $order;
        });

        session()->forget($this->cartSessionKey($trader));

        return redirect()
            ->route('front.trader.orders.show', $order->order_no)
            ->with(
                'success',
                $isArabic
                    ? 'تم إرسال طلب الجملة بنجاح. رقم الطلب: '.$order->order_no
                    : 'Wholesale order submitted successfully. Order no: '.$order->order_no
            );
    }

    protected function buildCartItem(
        Trader $trader,
        Product $product,
        int $colorId,
        int $seriesGroup,
        int $seriesCount,
        string $notes,
        array $currentCart,
        ?string $exceptCartKey,
    ): array {
        $isArabic = app()->getLocale() === 'ar';
        $groupId = $trader->wholesale_customer_group_id;
        abort_if($groupId === null, 404);

        $product = Product::query()
            ->with(['category', 'wholesaleColors', 'productColors.filterColor', 'variants.size', 'structureColor'])
            ->whereKey($product->getKey())
            ->where('is_active', true)
            ->where('show_web', true)
            ->where('show_wholesale', true)
            ->firstOrFail();

        $availability = ProductWholesaleAvailability::query()
            ->with('wholesaleColor')
            ->where('product_id', $product->getKey())
            ->where('product_wholesale_color_id', $colorId)
            ->where('wholesale_customer_group_id', $groupId)
            ->where('max_quantity', '>', 0)
            ->first();

        if (! $availability) {
            throw ValidationException::withMessages([
                'series_count' => $isArabic
                    ? 'هذا اللون غير متاح لمجموعة الجملة المرتبطة بحسابك.'
                    : 'This color is not available for your wholesale group.',
            ]);
        }

        $maxSeries = max(0, (int) $availability->max_quantity);
        $existingColorSeriesCount = $this->cartSeriesCountForColor(
            cart: $currentCart,
            productId: (int) $product->getKey(),
            colorId: $colorId,
            exceptCartKey: $exceptCartKey,
        );

        if (($existingColorSeriesCount + $seriesCount) > $maxSeries) {
            $remaining = max(0, $maxSeries - $existingColorSeriesCount);

            throw ValidationException::withMessages([
                'series_count' => $isArabic
                    ? 'لا يمكنك طلب عدد سيريات أكبر من المتاح لهذا اللون. المتبقي حالياً: '.$remaining
                    : 'You cannot request more series than available for this color. Remaining now: '.$remaining,
            ]);
        }

        $seriesRows = $this->seriesRows($product, $colorId, $seriesGroup);

        if ($seriesRows->isEmpty()) {
            throw ValidationException::withMessages([
                'series_count' => $isArabic
                    ? 'لا توجد مقاسات مضبوطة لهذه السيرية حالياً.'
                    : 'No size quantities are configured for this series.',
            ]);
        }

        $presentation = $this->productPresentation->presentProduct($product, app()->getLocale(), [], null);
        $unitPrice = (float) (
            $presentation['base_price']
            ?? $presentation['price_current']
            ?? $presentation['compare_price']
            ?? $product->price
            ?? $product->compare_price
            ?? 0
        );

        if ($unitPrice <= 0) {
            throw ValidationException::withMessages([
                'series_count' => $isArabic
                    ? 'سعر هذا المنتج غير مضبوط في المعلومات الأساسية، لذلك لا يمكن إضافته إلى طلب الجملة.'
                    : 'This product price is not configured in the basic product information, so it cannot be added to the wholesale cart.',
            ]);
        }

        $piecesPerSeries = (int) $seriesRows->sum(fn ($row) => max(0, (int) $row->quantity));
        $lineTotal = $piecesPerSeries * $seriesCount * $unitPrice;
        $title = (string) ($presentation['title'] ?? $this->productTitle($product));
        $color = $availability->wholesaleColor;
        $colorName = $color
            ? ($isArabic
                ? ($color->color_name_ar ?? $color->color_name_en ?? $color->color_code ?? '')
                : ($color->color_name_en ?? $color->color_name_ar ?? $color->color_code ?? ''))
            : '';

        return [
            'cart_key' => $this->cartItemKey((int) $product->getKey(), $colorId, $seriesGroup),
            'product_id' => (int) $product->getKey(),
            'product_wholesale_color_id' => $colorId,
            'series_group' => $seriesGroup,
            'series_count' => $seriesCount,
            'available_series' => $maxSeries,
            'product_name' => $title,
            'product_model_no' => (string) $product->model_no,
            'color_name' => $colorName !== '' ? $colorName : ($isArabic ? 'لون جملة' : 'Wholesale Color'),
            'unit_price' => $unitPrice,
            'currency_ar' => $product->currency_ar ?: 'ل.س',
            'currency_en' => $product->currency_en ?: 'SYP',
            'price_label' => (string) (
                $presentation['base_price_label']
                ?? $presentation['price_label']
                ?? $presentation['compare_price_label']
                ?? number_format($unitPrice, 0).' '.($isArabic ? ($product->currency_ar ?: 'ل.س') : ($product->currency_en ?: 'SYP'))
            ),
            'pieces_per_series' => $piecesPerSeries,
            'line_total' => $lineTotal,
            'series_rows' => $seriesRows->map(fn ($row) => [
                'size_text' => (string) $row->size_text,
                'quantity_per_series' => (int) $row->quantity,
            ])->values()->all(),
            'notes' => $notes,
            'image_url' => (string) ($presentation['image'] ?? ''),
        ];
    }

    protected function seriesRows(Product $product, int $colorId, int $seriesGroup): Collection
    {
        $rows = ProductWholesaleQuantity::query()
            ->where('product_id', $product->getKey())
            ->where('product_wholesale_color_id', $colorId)
            ->where('series_group', $seriesGroup)
            ->where('quantity', '>', 0)
            ->orderByRaw('CAST(size_text AS UNSIGNED), size_text')
            ->get();

        if ($rows->isNotEmpty()) {
            return $rows;
        }

        return ProductWholesaleQuantity::query()
            ->where('product_id', $product->getKey())
            ->whereNull('product_wholesale_color_id')
            ->where('series_group', $seriesGroup)
            ->where('quantity', '>', 0)
            ->orderByRaw('CAST(size_text AS UNSIGNED), size_text')
            ->get();
    }

    protected function cart(): array
    {
        /** @var Trader|null $trader */
        $trader = Auth::guard('trader')->user();

        if (! $trader) {
            return [];
        }

        return session()->get($this->cartSessionKey($trader), []);
    }

    protected function cartSessionKey(Trader $trader): string
    {
        return 'front_trader_wholesale_cart_'.$trader->getKey();
    }

    protected function cartItemKey(int $productId, int $colorId, int $seriesGroup): string
    {
        return $productId.'-'.$colorId.'-'.$seriesGroup;
    }

    protected function cartSeriesCountForColor(array $cart, int $productId, int $colorId, ?string $exceptCartKey = null): int
    {
        $total = 0;

        foreach ($cart as $key => $item) {
            if ($exceptCartKey !== null && $key === $exceptCartKey) {
                continue;
            }

            if ((int) ($item['product_id'] ?? 0) === $productId && (int) ($item['product_wholesale_color_id'] ?? 0) === $colorId) {
                $total += (int) ($item['series_count'] ?? 0);
            }
        }

        return $total;
    }

    protected function refreshedCart(Trader $trader): array
    {
        $cart = $this->cart();
        $refreshed = [];
        $warning = null;

        foreach ($cart as $cartKey => $item) {
            try {
                $product = Product::query()->findOrFail((int) ($item['product_id'] ?? 0));

                $refreshed[$cartKey] = $this->buildCartItem(
                    trader: $trader,
                    product: $product,
                    colorId: (int) ($item['product_wholesale_color_id'] ?? 0),
                    seriesGroup: (int) ($item['series_group'] ?? 0),
                    seriesCount: (int) ($item['series_count'] ?? 1),
                    notes: (string) ($item['notes'] ?? ''),
                    currentCart: $cart,
                    exceptCartKey: $cartKey,
                );
            } catch (\Throwable) {
                $warning = app()->getLocale() === 'ar'
                    ? 'تم حذف بند غير صالح من طلب الجملة المؤقت لأن السعر أو التوافر لم يعد مضبوطاً.'
                    : 'An invalid item was removed from the wholesale cart because price or availability is no longer configured.';
            }
        }

        session()->put($this->cartSessionKey($trader), $refreshed);

        return [$refreshed, $warning];
    }

    protected function cartSummary(array $cart): array
    {
        $series = 0;
        $pieces = 0;
        $total = 0.0;

        foreach ($cart as $item) {
            $series += (int) ($item['series_count'] ?? 0);
            $pieces += (int) ($item['pieces_per_series'] ?? 0) * (int) ($item['series_count'] ?? 0);
            $total += (float) ($item['line_total'] ?? 0);
        }

        return [
            'items_count' => count($cart),
            'series_count' => $series,
            'pieces_count' => $pieces,
            'total' => $total,
        ];
    }

    protected function productTitle(Product $product): string
    {
        $isArabic = app()->getLocale() === 'ar';

        return $isArabic
            ? ($product->title_ar ?: $product->title_en ?: $product->model_no)
            : ($product->title_en ?: $product->title_ar ?: $product->model_no);
    }

    protected function groupName(Trader $trader): ?string
    {
        $group = $trader->wholesaleCustomerGroup;
        if (! $group) {
            return null;
        }

        $isArabic = app()->getLocale() === 'ar';

        return $isArabic
            ? ($group->name_ar ?? $group->name_en ?? $group->name ?? '#'.$group->id)
            : ($group->name_en ?? $group->name_ar ?? $group->name ?? '#'.$group->id);
    }

    protected function validationMessages(bool $isArabic): array
    {
        return [
            'product_wholesale_color_id.required' => $isArabic ? 'يرجى اختيار لون الجملة.' : 'Please select a wholesale color.',
            'series_group.required' => $isArabic ? 'يرجى اختيار السيرية.' : 'Please select a series.',
            'series_count.required' => $isArabic ? 'يرجى إدخال عدد السيريات المطلوبة.' : 'Please enter the requested series count.',
            'series_count.min' => $isArabic ? 'عدد السيريات يجب أن يكون واحداً على الأقل.' : 'Series count must be at least one.',
            'notes.max' => $isArabic ? 'الملاحظات طويلة جداً.' : 'Notes are too long.',
        ];
    }
}
