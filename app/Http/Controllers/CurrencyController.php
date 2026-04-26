<?php

namespace App\Http\Controllers;

use App\Services\FrontCartService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\View;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    public function update(Request $request, FrontCartService $cart): JsonResponse|RedirectResponse
    {
        $currency = strtoupper((string) $request->input('currency', 'SYP'));

        if (! in_array($currency, ['SYP', 'USD', 'EUR'], true)) {
            $currency = 'SYP';
        }

        session(['selectedCurrency' => $currency]);
        cookie()->queue(cookie('selectedCurrency', $currency, 60 * 24 * 365));
        app()->instance('currentCurrency', $currency);
        View::share('currentCurrency', $currency);

        if ($request->expectsJson() || $request->ajax()) {
            $cartState = $cart->state();

            return response()->json([
                'success' => true,
                'currency' => $currency,
                'cart_state' => $cartState,
                'cart_html' => view('frontend.partials.shopping-cart', [
                    'cartState' => $cartState,
                ])->render(),
            ]);
        }

        return redirect()->back();
    }
}
