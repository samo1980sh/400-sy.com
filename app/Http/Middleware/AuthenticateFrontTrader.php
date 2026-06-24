<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFrontTrader
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('trader')->check()) {
            return redirect()->route('front.trader.login');
        }

        return $next($request);
    }
}