<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('front.contact.mail_heading') }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;color:#222;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f4f4;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:680px;background:#ffffff;border:1px solid #e5e5e5;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="background:#111111;color:#ffffff;padding:28px 32px;">
                            <div style="font-size:13px;opacity:.75;margin-bottom:8px;">{{ config('app.name') }}</div>
                            <div style="font-size:24px;font-weight:700;line-height:1.4;">{{ __('front.contact.mail_heading') }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:30px 32px;">
                            <p style="margin:0 0 24px;line-height:1.8;color:#555;">{{ __('front.contact.mail_intro') }}</p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="border-collapse:collapse;">
                                <tr>
                                    <td style="padding:11px 0;border-bottom:1px solid #eeeeee;font-weight:700;width:150px;">{{ __('front.contact.name') }}</td>
                                    <td style="padding:11px 0;border-bottom:1px solid #eeeeee;">{{ $messageData['name'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:11px 0;border-bottom:1px solid #eeeeee;font-weight:700;">{{ __('front.contact.email') }}</td>
                                    <td style="padding:11px 0;border-bottom:1px solid #eeeeee;direction:ltr;text-align:left;">{{ $messageData['email'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:11px 0;border-bottom:1px solid #eeeeee;font-weight:700;">{{ __('front.contact.phone') }}</td>
                                    <td style="padding:11px 0;border-bottom:1px solid #eeeeee;direction:ltr;text-align:left;">{{ $messageData['phone'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:11px 0;border-bottom:1px solid #eeeeee;font-weight:700;">{{ __('front.contact.subject') }}</td>
                                    <td style="padding:11px 0;border-bottom:1px solid #eeeeee;">{{ $messageData['subject'] }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:11px 0;border-bottom:1px solid #eeeeee;font-weight:700;">{{ __('front.contact.mail_sent_at') }}</td>
                                    <td style="padding:11px 0;border-bottom:1px solid #eeeeee;direction:ltr;text-align:left;">{{ now()->format('Y-m-d H:i:s T') }}</td>
                                </tr>
                            </table>

                            <div style="margin-top:26px;font-weight:700;">{{ __('front.contact.message') }}</div>
                            <div style="margin-top:10px;padding:18px;background:#f8f8f8;border-radius:8px;line-height:1.9;white-space:pre-wrap;">{{ $messageData['message'] }}</div>

                            <p style="margin:24px 0 0;color:#666;font-size:13px;line-height:1.7;">{{ __('front.contact.mail_reply_note') }}</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
