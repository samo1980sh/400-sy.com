<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFrontCustomerAddressRequest;
use App\Http\Requests\UpdateFrontCustomerPasswordRequest;
use App\Http\Requests\UpdateFrontCustomerProfileRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Services\FrontHomePageDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FrontCustomerAccountController extends Controller
{
    public function __construct(protected FrontHomePageDataService $homePageData)
    {
    }

    public function index(): View
    {
        $customer = $this->customer();
        $latestOrders = $customer->orders()
            ->latest('created_at')
            ->withCount('items')
            ->limit(5)
            ->get();

        return view('frontend.pages.account.index', $this->viewData([
            'page_title' => __('front.account.dashboard'),
            'page_subtitle' => __('front.account.dashboard_subtitle'),
            'customer' => $customer,
            'orders_count' => $customer->orders()->count(),
            'pending_orders_count' => $customer->orders()->whereIn('status', ['pending', 'confirmed', 'shipped'])->count(),
            'latest_orders' => $latestOrders,
            'default_address' => $customer->addresses()->where('is_default', true)->first(),
        ]));
    }

    public function profile(): View
    {
        return view('frontend.pages.account.profile', $this->viewData([
            'page_title' => __('front.account.profile'),
            'page_subtitle' => __('front.account.profile_subtitle'),
            'customer' => $this->customer(),
        ]));
    }

    public function updateProfile(UpdateFrontCustomerProfileRequest $request): RedirectResponse
    {
        $customer = $this->customer();
        $customer->fill($request->validated())->save();

        return back()->with('account_success', __('front.account.profile_updated'));
    }

    public function updatePassword(UpdateFrontCustomerPasswordRequest $request): RedirectResponse
    {
        $customer = $this->customer();
        $data = $request->validated();
        $customer->forceFill(['password' => $data['password']])->save();

        return back()->with('account_success', __('front.account.password_updated'));
    }

    public function addresses(): View
    {
        return view('frontend.pages.account.addresses', $this->viewData([
            'page_title' => __('front.account.addresses'),
            'page_subtitle' => __('front.account.addresses_subtitle'),
            'customer' => $this->customer(),
            'addresses' => $this->customer()->addresses()->orderByDesc('is_default')->latest('id')->get(),
        ]));
    }

    public function storeAddress(StoreFrontCustomerAddressRequest $request): RedirectResponse
    {
        $customer = $this->customer();
        $data = $request->validated();
        $data['is_default'] = (bool) ($data['is_default'] ?? false) || ! $customer->addresses()->exists();
        $customer->addresses()->create($data);

        return back()->with('account_success', __('front.account.address_created'));
    }

    public function updateAddress(
        StoreFrontCustomerAddressRequest $request,
        CustomerAddress $address,
    ): RedirectResponse {
        $address = $this->ownedAddress($address);
        $address->fill($request->validated())->save();

        return back()->with('account_success', __('front.account.address_updated'));
    }

    public function setDefaultAddress(CustomerAddress $address): RedirectResponse
    {
        $address = $this->ownedAddress($address);
        $address->forceFill(['is_default' => true])->save();

        return back()->with('account_success', __('front.account.default_address_updated'));
    }

    public function destroyAddress(CustomerAddress $address): RedirectResponse
    {
        $customer = $this->customer();
        $address = $this->ownedAddress($address);

        if ($customer->addresses()->count() <= 1) {
            return back()->withErrors(['address' => __('front.account.keep_one_address')]);
        }

        $wasDefault = (bool) $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $nextAddress = $customer->addresses()->latest('id')->first();
            $nextAddress?->forceFill(['is_default' => true])->save();
        }

        return back()->with('account_success', __('front.account.address_deleted'));
    }

    public function orders(): View
    {
        $orders = $this->customer()->orders()
            ->latest('created_at')
            ->withCount('items')
            ->paginate(10);

        return view('frontend.pages.account.orders', $this->viewData([
            'page_title' => __('front.account.orders'),
            'page_subtitle' => __('front.account.orders_subtitle'),
            'customer' => $this->customer(),
            'orders' => $orders,
        ]));
    }

    public function showOrder(Order $order): View
    {
        abort_unless((int) $order->customer_id === (int) $this->customer()->getKey(), 404);

        $order->load([
            'items.product',
            'shippingMethod',
            'shippingAddress',
            'statusHistory' => fn ($query) => $query->latest('created_at')->latest('id'),
        ]);

        $paymentMethod = PaymentMethod::query()
            ->where('code', $order->payment_method)
            ->first();

        return view('frontend.pages.account.order-show', $this->viewData([
            'page_title' => __('front.account.order_details'),
            'page_subtitle' => $order->order_no,
            'customer' => $this->customer(),
            'order' => $order,
            'payment_method_record' => $paymentMethod,
        ]));
    }

    protected function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer instanceof Customer, 403);

        return $customer;
    }

    protected function ownedAddress(CustomerAddress $address): CustomerAddress
    {
        abort_unless((int) $address->customer_id === (int) $this->customer()->getKey(), 404);

        return $address;
    }

    protected function viewData(array $data): array
    {
        $pageTitle = $data['page_title'] ?? __('front.account.title');

        return array_merge($this->homePageData->build(), [
            'breadcrumb_items' => [
                ['label' => __('front.nav.home'), 'url' => route('front.home')],
                ['label' => __('front.account.title'), 'url' => route('front.account.index')],
                ['label' => $pageTitle, 'url' => request()->url()],
            ],
        ], $data);
    }
}
