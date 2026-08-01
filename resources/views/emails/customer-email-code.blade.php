<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('customer_auth.email_subject_' . $purpose) }}</title>
</head>
<body style="margin:0;background:#f5f5f5;font-family:Arial,Helvetica,sans-serif;color:#222;">
    <div style="max-width:560px;margin:0 auto;padding:32px 16px;">
        <div style="background:#fff;border:1px solid #e8e8e8;border-radius:14px;padding:32px;text-align:{{ app()->getLocale() === 'ar' ? 'right' : 'left' }};">
            <h1 style="font-size:22px;margin:0 0 16px;">{{ __('customer_auth.email_heading_' . $purpose) }}</h1>
            <p style="font-size:15px;line-height:1.8;margin:0 0 22px;">{{ __('customer_auth.email_intro_' . $purpose) }}</p>

            <div style="direction:ltr;text-align:center;font-size:34px;font-weight:700;letter-spacing:10px;background:#faf7ef;border:1px solid #ead9ad;border-radius:10px;padding:18px 12px;margin:0 0 22px;">
                {{ $code }}
            </div>

            <p style="font-size:14px;line-height:1.8;margin:0 0 10px;">
                {{ __('customer_auth.email_expires', ['minutes' => $expiresMinutes]) }}
            </p>
            <p style="font-size:13px;line-height:1.8;color:#666;margin:0;">
                {{ __('customer_auth.email_ignore') }}
            </p>
        </div>
    </div>
</body>
</html>
