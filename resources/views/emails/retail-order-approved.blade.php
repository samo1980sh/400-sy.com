<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>تم اعتماد طلبك {{ $order->order_no }}</title>
</head>
<body style="margin:0;background:#f5f5f5;font-family:Arial,Tahoma,sans-serif;color:#1f2937;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f5f5f5;padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:720px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">
                <tr>
                    <td style="padding:28px 30px;background:#111827;color:#ffffff;">
                        <h1 style="margin:0 0 8px;font-size:24px;">تم اعتماد طلبك</h1>
                        <div style="direction:ltr;text-align:right;font-size:15px;opacity:.85;">{{ $order->order_no }}</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:28px 30px;">
                        <p style="margin:0 0 18px;line-height:1.9;">تمت مراجعة طلبك واعتماد الكميات المتوفرة. يعرض الجدول التالي الكمية المطلوبة والكمية النهائية المعتمدة.</p>

                        <table role="presentation" width="100%" cellspacing="0" cellpadding="8" style="border-collapse:collapse;font-size:14px;">
                            <thead>
                            <tr style="background:#f3f4f6;">
                                <th align="right" style="border:1px solid #e5e7eb;">المنتج</th>
                                <th align="center" style="border:1px solid #e5e7eb;">المطلوبة</th>
                                <th align="center" style="border:1px solid #e5e7eb;">المعتمدة</th>
                                <th align="left" style="border:1px solid #e5e7eb;">الإجمالي المعتمد</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td style="border:1px solid #e5e7eb;">{{ $item->product_name_snapshot ?: '—' }}</td>
                                    <td align="center" style="border:1px solid #e5e7eb;">{{ $item->quantity }}</td>
                                    <td align="center" style="border:1px solid #e5e7eb;font-weight:700;">{{ $item->approved_quantity ?? $item->quantity }}</td>
                                    <td align="left" dir="ltr" style="border:1px solid #e5e7eb;">{{ number_format((float) $item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        @if ($order->requested_total !== null && abs((float) $order->requested_total - (float) $order->total) > 0.009)
                            <div style="margin-top:20px;padding:16px;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;">
                                <div style="margin-bottom:8px;">الإجمالي المطلوب: <span dir="ltr" style="text-decoration:line-through;">{{ number_format((float) $order->requested_total, 2) }}</span></div>
                                <div style="font-size:18px;font-weight:700;">الإجمالي المعتمد: <span dir="ltr">{{ number_format((float) $order->total, 2) }}</span></div>
                            </div>
                        @else
                            <div style="margin-top:20px;font-size:18px;font-weight:700;">إجمالي الطلب: <span dir="ltr">{{ number_format((float) $order->total, 2) }}</span></div>
                        @endif

                        @if ($order->customer_id)
                            <p style="margin:24px 0 0;">
                                <a href="{{ route('front.account.orders.show', $order->order_no) }}" style="display:inline-block;background:#111827;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:8px;">عرض تفاصيل الطلب</a>
                            </p>
                        @endif

                        <hr style="border:0;border-top:1px solid #e5e7eb;margin:30px 0;">

                        <div dir="ltr" style="text-align:left;">
                            <h2 style="margin:0 0 10px;font-size:20px;">Your order has been confirmed</h2>
                            <p style="margin:0;line-height:1.7;color:#4b5563;">We reviewed your order and approved the available quantities. Please sign in to your account to review the requested and approved quantities and the final total.</p>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
