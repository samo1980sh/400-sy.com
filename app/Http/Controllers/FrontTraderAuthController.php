<?php

namespace App\Http\Controllers;

use App\Models\Trader;
use App\Services\FrontHomePageDataService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class FrontTraderAuthController extends Controller
{
    public function __construct(protected FrontHomePageDataService $homePageData)
    {
    }

    public function entry(): RedirectResponse
    {
        return redirect()->route(
            Auth::guard('trader')->check()
                ? 'front.trader.dashboard'
                : 'front.trader.login'
        );
    }

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('trader')->check()) {
            return redirect()->route('front.trader.dashboard');
        }

        $isArabic = app()->getLocale() === 'ar';

        return view('frontend.pages.trader.login', array_merge($this->homePageData->build(), [
            'page_title' => $isArabic ? 'دخول التجار' : 'Trader Login',
            'page_subtitle' => $isArabic
                ? 'تسجيل دخول تجار الجملة المعتمدين.'
                : 'Login for approved wholesale traders.',
            'breadcrumb_items' => [
                ['label' => $isArabic ? 'دخول التجار' : 'Trader Login', 'url' => null],
            ],
        ]));
    }

    public function login(Request $request): RedirectResponse
    {
        if (Auth::guard('trader')->check()) {
            return redirect()->route('front.trader.dashboard');
        }

        $isArabic = app()->getLocale() === 'ar';

        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'login.required' => $isArabic ? 'يرجى إدخال رقم الحساب أو الجوال.' : 'Please enter account number or mobile.',
            'password.required' => $isArabic ? 'يرجى إدخال كلمة المرور.' : 'Please enter the password.',
        ]);

        $login = trim((string) $validated['login']);
        $password = (string) $validated['password'];

        $trader = Trader::query()
            ->where('account_no', $login)
            ->orWhere('mobile', $login)
            ->first();

        if (! $trader instanceof Trader || ! Hash::check($password, (string) $trader->password)) {
            return back()
                ->withErrors(['login' => $isArabic ? 'بيانات الدخول غير صحيحة.' : 'Invalid login credentials.'])
                ->withInput($request->only('login'));
        }

        if ($trader->status !== 'active') {
            return back()
                ->withErrors(['login' => $isArabic ? 'حساب التاجر غير مفعل حالياً.' : 'Trader account is not active.'])
                ->withInput($request->only('login'));
        }

        Auth::guard('trader')->login($trader);
        $request->session()->migrate(true);

        return redirect()->intended(route('front.trader.dashboard'));
    }

    public function dashboard(): View
    {
        /** @var Trader $trader */
        $trader = Auth::guard('trader')->user();
        $trader->loadMissing('wholesaleCustomerGroup');

        $isArabic = app()->getLocale() === 'ar';

        return view('frontend.pages.trader.dashboard', array_merge($this->homePageData->build(), [
            'page_title' => $isArabic ? 'لوحة التاجر' : 'Trader Dashboard',
            'page_subtitle' => $isArabic
                ? 'ملخص حساب تاجر الجملة.'
                : 'Wholesale trader account overview.',
            'breadcrumb_items' => [
                ['label' => $isArabic ? 'لوحة التاجر' : 'Trader Dashboard', 'url' => null],
            ],
            'trader' => $trader,
            'orders_count' => $trader->orders()->count(),
            'latest_orders' => $trader->orders()->latest()->limit(5)->get(),
            'trader_cart_count' => count(session()->get('front_trader_wholesale_cart_'.$trader->getKey(), [])),
        ]));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('trader')->logout();
        $request->session()->migrate(true);

        return redirect()->route('front.trader.login');
    }
}
