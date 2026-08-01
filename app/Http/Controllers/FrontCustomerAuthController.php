<?php

namespace App\Http\Controllers;

use App\Http\Requests\FrontCustomerActivateRequest;
use App\Http\Requests\FrontCustomerActivationCodeRequest;
use App\Http\Requests\FrontCustomerForgotPasswordRequest;
use App\Http\Requests\FrontCustomerLoginRequest;
use App\Http\Requests\FrontCustomerPasswordResetCodeRequest;
use App\Http\Requests\FrontCustomerRegisterRequest;
use App\Models\Customer;
use App\Services\CustomerEmailCodeService;
use App\Services\FrontCustomerAccountService;
use App\Services\FrontWishlistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class FrontCustomerAuthController extends Controller
{
    public function __construct(
        protected FrontCustomerAccountService $accounts,
        protected FrontWishlistService $wishlist,
        protected CustomerEmailCodeService $emailCodes,
    ) {
    }

    public function login(FrontCustomerLoginRequest $request): RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('front.account.index');
        }

        $data = $request->validated();
        $customer = $this->accounts->findForLogin((string) $data['login']);

        if (! $customer instanceof Customer || ! $this->accounts->passwordMatches($customer, (string) $data['password'])) {
            return back()
                ->withErrors(['login' => __('front.auth.invalid_credentials')], 'customerLogin')
                ->withInput($request->safe()->only('login'))
                ->with('auth_modal', 'login');
        }

        if ($customer->status !== 'active') {
            return back()
                ->withErrors(['login' => __('front.auth.account_inactive')], 'customerLogin')
                ->withInput($request->safe()->only('login'))
                ->with('auth_modal', 'login');
        }

        Auth::guard('customer')->login($customer);
        $request->session()->migrate(true);
        $this->wishlist->mergeSessionIntoCustomer($customer);

        return redirect()->intended(route('front.account.index'));
    }

    public function register(FrontCustomerRegisterRequest $request): RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('front.account.index');
        }

        try {
            $customer = $this->accounts->register($request->validated());
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors(), 'customerRegister')
                ->withInput($request->safe()->except(['password', 'password_confirmation']))
                ->with('auth_modal', 'register');
        }

        Auth::guard('customer')->login($customer);
        $request->session()->migrate(true);
        $this->wishlist->mergeSessionIntoCustomer($customer);

        return redirect()
            ->route('front.account.index')
            ->with('account_success', __('front.auth.registration_success'));
    }

    public function requestActivationCode(FrontCustomerActivationCodeRequest $request): RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('front.account.index');
        }

        $validated = $request->validated();
        $email = (string) $validated['email'];

        try {
            $this->emailCodes->sendActivationCode($email, $request->ip());
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors(), 'customerActivationCode')
                ->withInput(['email' => $email])
                ->with('activation_email', $email)
                ->with('auth_modal', 'activateAccount');
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['email' => __('customer_auth.mail_send_failed')], 'customerActivationCode')
                ->withInput(['email' => $email])
                ->with('activation_email', $email)
                ->with('auth_modal', 'activateAccount');
        }

        return back()
            ->with('auth_notice', __('customer_auth.activation_code_sent'))
            ->with('activation_email', $email)
            ->with('auth_modal', 'activateAccount');
    }

    public function activate(FrontCustomerActivateRequest $request): RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('front.account.index');
        }

        $data = $request->validated();

        try {
            $customer = $this->emailCodes->activateAccount(
                email: (string) $data['email'],
                code: (string) $data['code'],
                password: (string) $data['password'],
            );
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors(), 'customerActivate')
                ->withInput($request->safe()->except(['password', 'password_confirmation']))
                ->with('activation_email', (string) $data['email'])
                ->with('auth_modal', 'activateAccount');
        }

        Auth::guard('customer')->login($customer);
        $request->session()->migrate(true);
        $this->wishlist->mergeSessionIntoCustomer($customer);

        return redirect()
            ->route('front.account.index')
            ->with('account_success', __('customer_auth.activation_success'));
    }

    public function requestPasswordResetCode(FrontCustomerPasswordResetCodeRequest $request): RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('front.account.index');
        }

        $validated = $request->validated();
        $email = (string) $validated['email'];

        try {
            $this->emailCodes->sendPasswordResetCode($email, $request->ip());
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors(), 'customerPasswordResetCode')
                ->withInput(['email' => $email])
                ->with('password_reset_email', $email)
                ->with('auth_modal', 'forgotPassword');
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['email' => __('customer_auth.mail_send_failed')], 'customerPasswordResetCode')
                ->withInput(['email' => $email])
                ->with('password_reset_email', $email)
                ->with('auth_modal', 'forgotPassword');
        }

        return back()
            ->with('auth_notice', __('customer_auth.password_reset_code_sent'))
            ->with('password_reset_email', $email)
            ->with('auth_modal', 'forgotPassword');
    }

    public function forgotPassword(FrontCustomerForgotPasswordRequest $request): RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('front.account.index');
        }

        $data = $request->validated();

        try {
            $this->emailCodes->resetPassword(
                email: (string) $data['email'],
                code: (string) $data['code'],
                password: (string) $data['password'],
            );
        } catch (ValidationException $exception) {
            return back()
                ->withErrors($exception->errors(), 'customerForgotPassword')
                ->withInput($request->safe()->except(['password', 'password_confirmation']))
                ->with('password_reset_email', (string) $data['email'])
                ->with('auth_modal', 'forgotPassword');
        }

        return back()
            ->with('account_success', __('customer_auth.password_reset_success'))
            ->with('auth_modal', 'login');
    }

    public function logout(Request $request): RedirectResponse
    {
        $customer = Auth::guard('customer')->user();

        if ($customer instanceof Customer) {
            $this->wishlist->copyCustomerWishlistToSession($customer);
        }

        Auth::guard('customer')->logout();
        $request->session()->migrate(true);

        return redirect()
            ->route('front.home')
            ->with('account_success', __('front.auth.logout_success'));
    }
}
