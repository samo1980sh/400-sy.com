<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class SetFrontLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale', 'en'));
        $currency = $this->resolveCurrency($request);

        if (! in_array($locale, ['ar', 'en'], true)) {
            $locale = 'en';
        }

        app()->setLocale($locale);
        app()->instance('currentCurrency', $currency);
        session(['selectedCurrency' => $currency]);
        cookie()->queue(cookie('selectedCurrency', $currency, 60 * 24 * 365));
        View::share('currentCurrency', $currency);

        return $next($request);
    }

    protected function resolveCurrency(Request $request): string
    {
        $sessionCurrency = $this->normalizeCurrency(session('selectedCurrency'));
        if ($sessionCurrency !== null) {
            return $sessionCurrency;
        }

        $cookieCurrency = $this->normalizeCurrency($request->cookie('selectedCurrency'));
        if ($cookieCurrency !== null) {
            return $cookieCurrency;
        }

        return $this->detectCurrencyByIp($request);
    }

    protected function detectCurrencyByIp(Request $request): string
    {
        $ip = $request->ip();
        $databasePath = storage_path('app/geoip/GeoLite2-Country.mmdb');

        if (! filter_var($ip, FILTER_VALIDATE_IP) || ! is_file($databasePath) || ! class_exists(\GeoIp2\Database\Reader::class)) {
            return 'USD';
        }

        try {
            $reader = new \GeoIp2\Database\Reader($databasePath);

            try {
                $country = $reader->country($ip)->country->isoCode;
            } finally {
                if (method_exists($reader, 'close')) {
                    $reader->close();
                }
            }
        } catch (\Throwable) {
            return 'USD';
        }

        return strtoupper((string) $country) === 'SY' ? 'SYP' : 'USD';
    }

    protected function normalizeCurrency(mixed $currency): ?string
    {
        $currency = strtoupper(trim((string) $currency));

        if (! in_array($currency, ['SYP', 'USD', 'EUR'], true)) {
            return null;
        }

        return $currency;
    }
}
