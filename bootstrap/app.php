<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'front.locale' => \App\Http\Middleware\SetFrontLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if (! $request->routeIs('front.customer.*')) {
                return null;
            }

            $routeName = (string) $request->route()?->getName();

            [$modal, $errorBag] = match ($routeName) {
                'front.customer.activate.code' => ['activateAccount', 'customerActivationCode'],
                'front.customer.activate' => ['activateAccount', 'customerActivate'],
                'front.customer.forgot-password.code' => ['forgotPassword', 'customerPasswordResetCode'],
                'front.customer.forgot-password' => ['forgotPassword', 'customerForgotPassword'],
                'front.customer.register' => ['register', 'customerRegister'],
                default => ['login', 'customerLogin'],
            };

            $message = __('customer_auth.too_many_requests');

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                ], 429)->withHeaders($exception->getHeaders());
            }

            return redirect()->back()
                ->withErrors(['throttle' => $message], $errorBag)
                ->withInput($request->except(['password', 'password_confirmation', 'code']))
                ->with('auth_modal', $modal);
        });
    })->create();
