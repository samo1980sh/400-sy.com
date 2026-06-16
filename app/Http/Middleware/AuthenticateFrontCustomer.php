<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFrontCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer || $customer->status !== 'active') {
            Auth::guard('customer')->logout();

            return redirect()
                ->guest(route('front.home'))
                ->with('auth_modal', 'login');
        }

        return $next($request);
    }
}
