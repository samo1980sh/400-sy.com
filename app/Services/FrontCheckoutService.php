<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\PointVoucherRedemption;
use App\Models\OrderStatusHistory;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class FrontCheckoutService
{
    public const SUCCESS_SESSION_KEY = 'front.checkout.last_order_id';

    public function __construct(
        protected FrontCartService $cart,
        protected OrderCouponService $coupons,
        protected OrderPointVoucherService $pointVouchers,
    ) {
    }

    public function activeShippingMethods(): EloquentCollection
    {
        return ShippingMethod::query()
            ->where('active', true)
            ->orderBy('cost')
            ->orderBy('id')
            ->get();
    }

    public function activePaymentMethods(): EloquentCollection
    {
        return PaymentMethod::query()
            ->where('active', true)
            ->orderBy('id')
            ->get();
    }

    public function createOrder(array $data): Order
    {
        $order = DB::transaction(function () use ($data): Order {
            $cartState = $this->cart->checkoutState();
            $items = collect($cartState['items'] ?? []);
            $couponCode = trim((string) ($data['coupon_code'] ?? ''));

            if ($couponCode !== '' && ! (Auth::guard('customer')->user() instanceof Customer)) {
                throw ValidationException::withMessages([
                    'coupon_code' => __('front.checkout.coupon_customer_required'),
                ]);
            }

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => __('front.checkout.cart_empty'),
                ]);
            }

            $shippingMethod = ShippingMethod::query()
                ->where('active', true)
                ->find($data['shipping_method_id']);

            if (! $shippingMethod instanceof ShippingMethod) {
                throw ValidationException::withMessages([
                    'shipping_method_id' => __('front.checkout.shipping_unavailable'),
                ]);
            }

            $paymentMethod = PaymentMethod::query()
                ->where('active', true)
                ->where('code', $data['payment_method'])
                ->first();

            if (! $paymentMethod instanceof PaymentMethod) {
                throw ValidationException::withMessages([
                    'payment_method' => __('front.checkout.payment_unavailable'),
                ]);
            }

            $customer = $this->resolveCustomer($data);
            $address = $this->resolveAddress($customer, $data);
            $subtotal = (int) ($cartState['subtotal'] ?? 0);
            $shippingCost = (int) round((float) $shippingMethod->cost);
            $total = $subtotal + $shippingCost;

            $order = Order::create([
                'customer_id' => $customer?->getKey(),
                'shipping_address_id' => $address?->getKey(),
                'shipping_method_id' => $shippingMethod->getKey(),
                'customer_name_snapshot' => $customer?->name ?? $data['full_name'],
                'customer_mobile_snapshot' => $customer?->mobile ?? $data['mobile'],
                'customer_email_snapshot' => $customer?->email ?? ($data['email'] ?? null),
                'customer_account_no_snapshot' => $customer?->account_no,
                'shipping_label_snapshot' => $address?->label ?? ($data['address_label'] ?: __('front.checkout.address_types.' . $data['address_type'])),
                'shipping_contact_name_snapshot' => $address?->contact_name ?? $data['full_name'],
                'shipping_mobile_snapshot' => $address?->mobile ?? $data['mobile'],
                'shipping_city_snapshot' => $address?->city ?? $data['city'],
                'shipping_area_snapshot' => $address?->area ?? $data['area'],
                'shipping_address_line_snapshot' => $address?->address_line ?? $data['address_line'],
                'shipping_address_type_snapshot' => $address?->address_type ?? $data['address_type'],
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $paymentMethod->code,
                'is_gift' => (bool) ($data['is_gift'] ?? false),
                'gift_message' => filled($data['gift_message'] ?? null)
                    ? trim((string) $data['gift_message'])
                    : null,
                'total_before_discount' => $subtotal,
                'discount_value' => 0,
                'coupon_discount_value' => 0,
                'shipping_cost' => $shippingCost,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
            ]);

            $productIds = $items->pluck('product_id')->filter()->map(fn ($id): int => (int) $id)->unique()->values();
            $variantIds = $items->pluck('variant_id')->filter()->map(fn ($id): int => (int) $id)->unique()->values();

            $products = Product::query()
                ->whereIn('id', $productIds)
                ->get()
                ->keyBy(fn (Product $product): int => (int) $product->getKey());

            $variants = ProductVariant::query()
                ->whereIn('id', $variantIds)
                ->get()
                ->keyBy(fn (ProductVariant $variant): int => (int) $variant->getKey());

            foreach ($items as $item) {
                $product = $products->get((int) ($item['product_id'] ?? 0));
                $variant = $variants->get((int) ($item['variant_id'] ?? 0));
                $quantity = max(1, (int) ($item['qty'] ?? 1));
                $unitPrice = (int) ($item['unit_price'] ?? $item['base_price'] ?? 0);

                $order->items()->create([
                    'product_id' => $product?->getKey(),
                    'product_variant_id' => $variant?->getKey(),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'product_name_snapshot' => $item['title'] ?? $product?->title_ar ?? $product?->title_en,
                    'product_model_no_snapshot' => $product?->model_no,
                    'product_sku_snapshot' => $variant?->sku,
                    'product_barcode_snapshot' => $variant?->barcode,
                    'color_name_snapshot' => $item['color_name'] ?? null,
                    'size_name_snapshot' => $item['size'] ?? null,
                    'line_total' => $unitPrice * $quantity,
                ]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->getKey(),
                'from_status' => null,
                'to_status' => 'pending',
                'from_payment_status' => null,
                'to_payment_status' => 'unpaid',
                'note' => __('front.checkout.order_created_history'),
                'changed_by' => null,
            ]);

            if ($couponCode !== '') {
                try {
                    if ($this->pointVouchers->looksLikePointVoucherCode($couponCode)) {
                        $this->pointVouchers->applyToOrder(
                            order: $order,
                            redemptionCode: $couponCode,
                            notes: __('front.checkout.point_voucher_checkout_note'),
                        );
                    } else {
                        $this->coupons->applyCoupon(
                            order: $order,
                            couponCode: $couponCode,
                            notes: __('front.checkout.coupon_checkout_note'),
                        );
                    }
                } catch (RuntimeException $exception) {
                    throw ValidationException::withMessages([
                        'coupon_code' => $exception->getMessage(),
                    ]);
                }
            }

            return $order;
        }, 3);

        $this->cart->clear();
        session()->put(self::SUCCESS_SESSION_KEY, (int) $order->getKey());

        return $order->load(['items', 'shippingMethod', 'customer', 'shippingAddress']);
    }

    protected function resolveCustomer(array $data): ?Customer
    {
        $authenticatedCustomer = Auth::guard('customer')->user();

        if ($authenticatedCustomer instanceof Customer) {
            $customerData = [
                'name' => $data['full_name'],
                'city' => $data['city'],
                'area' => $data['area'],
            ];

            if (filled($data['email'] ?? null)) {
                $customerData['email'] = $data['email'];
            }

            $authenticatedCustomer->fill($customerData)->save();

            return $authenticatedCustomer->refresh();
        }

        $customer = Customer::query()
            ->where('mobile', $data['mobile'])
            ->first();

        $customerData = [
            'name' => $data['full_name'],
            'city' => $data['city'],
            'area' => $data['area'],
        ];

        if (filled($data['email'] ?? null)) {
            $customerData['email'] = $data['email'];
        }

        if ($customer instanceof Customer) {
            return null;
        }

        return Customer::create(array_merge($customerData, [
            'account_no' => $this->generateCustomerAccountNo(),
            'mobile' => $data['mobile'],
            'status' => 'active',
        ]));
    }

    protected function resolveAddress(?Customer $customer, array $data): ?CustomerAddress
    {
        if (! $customer instanceof Customer) {
            return null;
        }

        $address = $customer->addresses()
            ->where('mobile', $data['mobile'])
            ->where('city', $data['city'])
            ->where('area', $data['area'])
            ->where('address_line', $data['address_line'])
            ->where('address_type', $data['address_type'])
            ->first();

        $addressData = [
            'label' => $data['address_label'] ?: __('front.checkout.address_types.' . $data['address_type']),
            'contact_name' => $data['full_name'],
            'mobile' => $data['mobile'],
            'city' => $data['city'],
            'area' => $data['area'],
            'address_line' => $data['address_line'],
            'address_type' => $data['address_type'],
        ];

        if ($address instanceof CustomerAddress) {
            $address->fill($addressData)->save();

            return $address->refresh();
        }

        return $customer->addresses()->create(array_merge($addressData, [
            'is_default' => ! $customer->addresses()->exists(),
        ]));
    }

    protected function generateCustomerAccountNo(): string
    {
        do {
            $accountNo = 'WEB-' . now()->format('ym') . '-' . strtoupper(Str::random(6));
        } while (Customer::query()->where('account_no', $accountNo)->exists());

        return $accountNo;
    }
}
