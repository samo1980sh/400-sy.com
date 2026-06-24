<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreFrontGiftCardRequestRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\GiftCardRequest;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use App\Services\FrontHomePageDataService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class FrontGiftCardRequestController extends Controller
{
    public function __construct(protected FrontHomePageDataService $homePageData)
    {
    }

    public function create(): View
    {
        $customer = Auth::guard('customer')->user();

        return view('frontend.pages.gift-cards.create', $this->pageData([
            'page_title' => 'طلب بطاقة هدية',
            'page_subtitle' => 'أرسل طلب بطاقة هدية وسيتم إرساله إلى لوحة الإدارة للمعالجة.',
            'customer' => $customer instanceof Customer ? $customer : null,
            'branches' => $this->activeRecords(Branch::class),
            'payment_methods' => $this->activeRecords(PaymentMethod::class),
            'shipping_methods' => $this->activeRecords(ShippingMethod::class),
        ]));
    }

    public function store(StoreFrontGiftCardRequestRequest $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer instanceof Customer, 403);

        $data = $request->validated();

        $shippingMethodId = null;
        $deliveryAddress = null;
        $deliveryFee = 0.0;
        $pickupBranchId = null;

        if ($data['fulfillment_method'] === 'delivery') {
            $shippingMethodId = (int) $data['shipping_method_id'];
            $deliveryAddress = $data['delivery_address'];
            $deliveryFee = $this->shippingCost($shippingMethodId);
        } else {
            $pickupBranchId = (int) $data['pickup_branch_id'];
        }

        $giftCardRequest = GiftCardRequest::query()->create([
            'customer_id' => $customer->getKey(),
            'display_name_type' => $data['display_name_type'],
            'requester_name' => $data['requester_name'],
            'recipient_name' => $data['recipient_name'] ?: null,
            'card_quantity' => (int) $data['card_quantity'],
            'recipient_mobile' => $data['recipient_mobile'] ?: null,
            'card_amount' => (float) $data['card_amount'],
            'currency' => $data['currency'],
            'fulfillment_method' => $data['fulfillment_method'],
            'pickup_branch_id' => $pickupBranchId,
            'shipping_method_id' => $shippingMethodId,
            'delivery_address' => $deliveryAddress,
            'delivery_fee' => $deliveryFee,
            'payment_method_id' => (int) $data['payment_method_id'],
            'redemption_branch_id' => (int) $data['redemption_branch_id'],
            'status' => 'pending',
            'payment_status' => 'pending',
            'customer_notes' => $data['customer_notes'] ?? null,
            'submitted_at' => now(),
        ]);

        return redirect()
            ->route('front.account.gift-card-requests.show', ['giftCardRequest' => $giftCardRequest->request_no])
            ->with('account_success', 'تم إرسال طلب بطاقة الهدية بنجاح، وسيتم مراجعته من قبل الإدارة.');
    }

    public function accountIndex(): View
    {
        $customer = $this->customer();

        $requests = GiftCardRequest::query()
            ->where('customer_id', $customer->getKey())
            ->withCount('giftCards')
            ->latest('created_at')
            ->paginate(10);

        return view('frontend.pages.account.gift-card-requests.index', $this->accountData([
            'page_title' => 'طلبات بطاقات الهدايا',
            'page_subtitle' => 'تابع طلبات بطاقات الهدايا الخاصة بك.',
            'customer' => $customer,
            'requests' => $requests,
        ]));
    }

    public function accountShow(GiftCardRequest $giftCardRequest): View
    {
        $customer = $this->customer();

        abort_unless((int) $giftCardRequest->customer_id === (int) $customer->getKey(), 404);

        $giftCardRequest->load([
            'pickupBranch',
            'shippingMethod',
            'paymentMethod',
            'redemptionBranch',
            'giftCards',
        ]);

        return view('frontend.pages.account.gift-card-requests.show', $this->accountData([
            'page_title' => 'تفاصيل طلب بطاقة الهدية',
            'page_subtitle' => $giftCardRequest->request_no,
            'customer' => $customer,
            'gift_card_request' => $giftCardRequest,
        ]));
    }

    protected function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();

        abort_unless($customer instanceof Customer, 403);

        return $customer;
    }

    protected function accountData(array $data): array
    {
        $pageTitle = $data['page_title'] ?? 'حسابي';

        return array_merge($this->homePageData->build(), [
            'breadcrumb_items' => [
                ['label' => __('front.nav.home'), 'url' => route('front.home')],
                ['label' => 'حسابي', 'url' => route('front.account.index')],
                ['label' => $pageTitle, 'url' => request()->url()],
            ],
        ], $data);
    }
    protected function pageData(array $data): array
    {
        return array_merge($this->homePageData->build(), [
            'breadcrumb_items' => [
                ['label' => __('front.nav.home'), 'url' => route('front.home')],
                ['label' => $data['page_title'] ?? 'طلب بطاقة هدية', 'url' => request()->url()],
            ],
        ], $data);
    }

    protected function activeRecords(string $modelClass): EloquentCollection
    {
        $model = new $modelClass();
        $table = $model->getTable();
        $query = $modelClass::query();

        if (Schema::hasColumn($table, 'is_active')) {
            $query->where('is_active', true);
        }

        foreach (['sort_order', 'name_ar', 'name', 'id'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                $query->orderBy($column);
                break;
            }
        }

        return $query->get();
    }

    protected function shippingCost(int $shippingMethodId): float
    {
        $shippingMethod = ShippingMethod::query()->find($shippingMethodId);

        if (! $shippingMethod instanceof ShippingMethod) {
            return 0.0;
        }

        foreach (['cost', 'delivery_fee', 'price', 'amount'] as $attribute) {
            if (isset($shippingMethod->{$attribute})) {
                return (float) $shippingMethod->{$attribute};
            }
        }

        return 0.0;
    }
}
