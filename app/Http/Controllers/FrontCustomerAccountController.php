<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFrontCustomerAddressRequest;
use App\Http\Requests\UpdateFrontCustomerPasswordRequest;
use App\Http\Requests\UpdateFrontCustomerProfileRequest;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerLoyaltyTransaction;
use App\Models\CustomerLoyaltyWallet;
use App\Models\CustomerQrCode;
use App\Models\Order;
use App\Models\OrderRating;
use App\Models\PointVoucherRedemption;
use App\Models\PointsVoucher;
use App\Models\PaymentMethod;
use App\Services\FrontHomePageDataService;
use App\Support\SimpleQrSvg;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
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

    public function qrCode(): View
    {
        $customer = $this->customer();
        $customer->loadMissing(['loyaltyWallet', 'qrCode']);

        $wallet = $customer->loyaltyWallet ?: CustomerLoyaltyWallet::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'points_balance' => 0,
                'points_earned_total' => 0,
                'points_spent_total' => 0,
                'status' => 'active',
            ]
        );

        $qrCode = $customer->qrCode ?: CustomerQrCode::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'token' => null,
                'status' => 'active',
                'generated_at' => now(),
                'scan_count' => 0,
            ]
        );

        $qrSvg = null;

        if ($qrCode->isActive()) {
            try {
                $qrSvg = SimpleQrSvg::svg((string) $qrCode->token, 14, 6);
            } catch (\Throwable) {
                $qrSvg = null;
            }
        }

        return view('frontend.pages.account.qr-code.index', $this->viewData([
            'page_title' => 'رمز QR الخاص بي',
            'page_subtitle' => 'استخدم هذا الرمز داخل الصالات لتعريف حسابك وربط العمليات بحساب الولاء.',
            'customer' => $customer,
            'wallet' => $wallet,
            'qr_code' => $qrCode,
            'qr_svg' => $qrSvg,
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
            'rating',
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

    public function storeOrderRating(Request $request, Order $order): RedirectResponse
    {
        $customer = $this->customer();

        abort_unless((int) $order->customer_id === (int) $customer->getKey(), 404);

        if ($order->status !== 'delivered') {
            return back()->withErrors(['rating' => 'يمكن تقييم الطلب بعد استلامه فقط.']);
        }

        if ($order->rating()->exists()) {
            return back()->withErrors(['rating' => 'تم تقييم هذا الطلب سابقاً.']);
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ], [
            'rating.required' => 'يرجى اختيار عدد النجوم.',
            'rating.min' => 'يرجى اختيار تقييم من نجمة إلى خمس نجوم.',
            'rating.max' => 'يرجى اختيار تقييم من نجمة إلى خمس نجوم.',
            'comment.max' => 'الملاحظة طويلة جداً.',
        ]);

        OrderRating::create([
            'order_id' => $order->id,
            'customer_id' => $customer->id,
            'rating' => (int) $validated['rating'],
            'comment' => filled($validated['comment'] ?? null)
                ? trim((string) $validated['comment'])
                : null,
        ]);

        return back()->with('account_success', 'شكراً لك، تم حفظ تقييم الطلب بنجاح.');
    }

    public function pointsVouchers(): View
    {
        $customer = $this->customer();
        $customer->loadMissing(['loyaltyWallet', 'retailGroups']);

        $wallet = $customer->loyaltyWallet ?: CustomerLoyaltyWallet::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'points_balance' => 0,
                'points_earned_total' => 0,
                'points_spent_total' => 0,
                'status' => 'active',
            ]
        );

        $groupIds = $customer->retailGroups
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $vouchers = PointsVoucher::query()
            ->where('status', 'active')
            ->where(function ($query) use ($groupIds): void {
                $query->whereNull('retail_customer_group_id');

                if ($groupIds !== []) {
                    $query->orWhereIn('retail_customer_group_id', $groupIds);
                }
            })
            ->with('customerGroup')
            ->orderBy('points_required')
            ->orderBy('voucher_value')
            ->get();

        $redemptions = $customer->pointVoucherRedemptions()
            ->with('voucher')
            ->latest('created_at')
            ->paginate(10);

        return view('frontend.pages.account.points-vouchers.index', $this->viewData([
            'page_title' => 'صرف النقاط',
            'page_subtitle' => 'اختر القسيمة المناسبة حسب رصيد نقاطك وفئتك.',
            'customer' => $customer,
            'wallet' => $wallet,
            'vouchers' => $vouchers,
            'redemptions' => $redemptions,
            'branches' => $this->frontBranches(),
        ]));
    }

    public function redeemPointsVoucher(Request $request, PointsVoucher $pointsVoucher): RedirectResponse
    {
        $customer = $this->customer();
        $customer->loadMissing('retailGroups');

        $validated = $request->validate([
            'usage_method' => ['required', Rule::in(['online', 'in_store'])],
            'branch' => ['nullable', 'string', 'max:255'],
        ], [
            'usage_method.required' => 'يرجى اختيار طريقة الصرف.',
            'usage_method.in' => 'طريقة الصرف المحددة غير صحيحة.',
            'branch.max' => 'اسم الفرع طويل جداً.',
        ]);

        if (($validated['usage_method'] ?? null) === 'in_store' && blank($validated['branch'] ?? null)) {
            return back()
                ->withInput()
                ->withErrors(['branch' => 'يرجى اختيار الفرع عند الصرف داخل الصالات.']);
        }

        if (($validated['usage_method'] ?? null) === 'online') {
            $validated['branch'] = null;
        }

        if ($pointsVoucher->status !== 'active') {
            return back()->withErrors(['points_voucher' => 'هذه القسيمة غير متاحة حالياً.']);
        }

        $groupIds = $customer->retailGroups
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        if (
            filled($pointsVoucher->retail_customer_group_id)
            && ! in_array((int) $pointsVoucher->retail_customer_group_id, $groupIds, true)
        ) {
            abort(404);
        }

        try {
            $redemption = DB::transaction(function () use ($customer, $pointsVoucher, $validated): PointVoucherRedemption {
                $wallet = CustomerLoyaltyWallet::query()
                    ->where('customer_id', $customer->id)
                    ->lockForUpdate()
                    ->first();

                if (! $wallet) {
                    $wallet = CustomerLoyaltyWallet::create([
                        'customer_id' => $customer->id,
                        'points_balance' => 0,
                        'points_earned_total' => 0,
                        'points_spent_total' => 0,
                        'status' => 'active',
                    ]);
                }

                if ($wallet->status !== 'active') {
                    throw new \RuntimeException('محفظة النقاط غير فعالة حالياً.');
                }

                $pointsRequired = (float) $pointsVoucher->points_required;
                $balanceBefore = (float) $wallet->points_balance;

                if ($balanceBefore < $pointsRequired) {
                    throw new \RuntimeException('رصيد النقاط غير كافٍ لصرف هذه القسيمة.');
                }

                $balanceAfter = $balanceBefore - $pointsRequired;
                $validDays = (int) ($pointsVoucher->valid_days ?: 30);

                $redemption = PointVoucherRedemption::create([
                    'customer_id' => $customer->id,
                    'points_voucher_id' => $pointsVoucher->id,
                    'customer_name' => $customer->name,
                    'account_no' => $customer->account_no,
                    'mobile' => $customer->mobile,
                    'voucher_value' => $pointsVoucher->voucher_value,
                    'points_spent' => $pointsVoucher->points_required,
                    'usage_method' => $validated['usage_method'],
                    'branch' => $validated['usage_method'] === 'in_store' ? ($validated['branch'] ?? null) : null,
                    'status' => 'available',
                    'issued_at' => now(),
                    'expires_at' => now()->addDays(max($validDays, 1)),
                    'notes' => $validated['usage_method'] === 'online'
                        ? 'تم إصدار كود قسيمة للاستخدام عبر الموقع.'
                        : 'تم إصدار قسيمة للصرف داخل الصالات.',
                ]);

                $wallet->forceFill([
                    'points_balance' => $balanceAfter,
                    'points_spent_total' => (float) $wallet->points_spent_total + $pointsRequired,
                ])->save();

                CustomerLoyaltyTransaction::create([
                    'customer_id' => $customer->id,
                    'customer_loyalty_wallet_id' => $wallet->id,
                    'type' => 'spend',
                    'points' => $pointsRequired,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'source_type' => 'point_voucher_redemption',
                    'source_id' => $redemption->id,
                    'reference_no' => $redemption->order_no,
                    'occurred_at' => now(),
                    'notes' => 'صرف نقاط مقابل قسيمة ' . $redemption->order_no,
                ]);

                return $redemption;
            });
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['points_voucher' => $exception->getMessage()]);
        }

        return redirect()
            ->route('front.account.points-vouchers.index')
            ->with('account_success', 'تم إصدار القسيمة بنجاح. الكود: ' . $redemption->order_no);
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


    protected function frontBranches(): array
    {
        if (! Schema::hasTable('branches')) {
            return [];
        }

        return DB::table('branches')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(function ($branch): array {
                $name = $branch->name_ar
                    ?? $branch->name
                    ?? $branch->name_en
                    ?? ('فرع #' . $branch->id);

                return [(string) $name => (string) $name];
            })
            ->all();
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
