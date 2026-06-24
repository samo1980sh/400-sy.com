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

    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('trader')->check()) {
            return redirect()->route('front.trader.dashboard');
        }

        return view('frontend.pages.trader.login', array_merge($this->homePageData->build(), [
            'page_title' => app()->getLocale() === 'ar' ? 'دخول التجار' : 'Trader Login',
            'page_subtitle' => app()->getLocale() === 'ar'
                ? 'تسجيل دخول تجار الجملة المعتمدين.'
                : 'Login for approved wholesale traders.',
            'breadcrumb_items' => [
                ['label' => __('front.nav.home'), 'url' => route('front.home')],
                ['label' => app()->getLocale() === 'ar' ? 'دخول التجار' : 'Trader Login', 'url' => null],
            ],
        ]));
    }

    public function login(Request $request): RedirectResponse
    {
        if (Auth::guard('trader')->check()) {
            return redirect()->route('front.trader.dashboard');
        }

        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'login.required' => app()->getLocale() === 'ar' ? 'يرجى إدخال رقم الحساب أو الجوال.' : 'Please enter account number or mobile.',
            'password.required' => app()->getLocale() === 'ar' ? 'يرجى إدخال كلمة المرور.' : 'Please enter the password.',
        ]);

        $login = trim((string) $validated['login']);
        $password = (string) $validated['password'];

        $trader = Trader::query()
            ->where('account_no', $login)
            ->orWhere('mobile', $login)
            ->first();

        if (! $trader instanceof Trader || ! Hash::check($password, (string) $trader->password)) {
            return back()
                ->withErrors(['login' => app()->getLocale() === 'ar' ? 'بيانات الدخول غير صحيحة.' : 'Invalid login credentials.'])
                ->withInput($request->only('login'));
        }

        if ($trader->status !== 'active') {
            return back()
                ->withErrors(['login' => app()->getLocale() === 'ar' ? 'حساب التاجر غير مفعل حالياً.' : 'Trader account is not active.'])
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

        return view('frontend.pages.trader.dashboard', array_merge($this->homePageData->build(), [
            'page_title' => app()->getLocale() === 'ar' ? 'لوحة التاجر' : 'Trader Dashboard',
            'page_subtitle' => app()->getLocale() === 'ar'
                ? 'ملخص حساب تاجر الجملة.'
                : 'Wholesale trader account overview.',
            'breadcrumb_items' => [
                ['label' => __('front.nav.home'), 'url' => route('front.home')],
                ['label' => app()->getLocale() === 'ar' ? 'لوحة التاجر' : 'Trader Dashboard', 'url' => null],
            ],
            'trader' => $trader,
            'orders_count' => $trader->orders()->count(),
            'latest_orders' => $trader->orders()->latest()->limit(5)->get(),
        ]));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('trader')->logout();
        $request->session()->migrate(true);

        return redirect()->route('front.trader.login');
    }
}