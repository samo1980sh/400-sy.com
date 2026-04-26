<div class="space-y-6">
    <div class="grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-gray-200 p-4">
            <div class="text-sm text-gray-500">رقم الطلب</div>
            <div class="mt-1 font-semibold">{{ $order->order_no }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 p-4">
            <div class="text-sm text-gray-500">التاجر</div>
            <div class="mt-1 font-semibold">{{ $order->trader?->name ?? '—' }}</div>
            <div class="text-sm text-gray-500">{{ $order->trader?->wholesaleCustomerGroup?->name_ar ?? '—' }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 p-4">
            <div class="text-sm text-gray-500">الحالة</div>
            <div class="mt-1 font-semibold">{{ $order->status }}</div>
        </div>
        <div class="rounded-xl border border-gray-200 p-4">
            <div class="text-sm text-gray-500">الدفع</div>
            <div class="mt-1 font-semibold">{{ $order->payment_status }}</div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200">
        <table class="w-full text-right text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 font-medium">المنتج</th>
                    <th class="px-4 py-3 font-medium">اللون</th>
                    <th class="px-4 py-3 font-medium">السيريات</th>
                    <th class="px-4 py-3 font-medium">الكمية</th>
                    <th class="px-4 py-3 font-medium">سعر القطعة</th>
                    <th class="px-4 py-3 font-medium">الإجمالي</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse ($order->items as $item)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $item->product_name_snapshot ?? '—' }}</div>
                            <div class="text-xs text-gray-500">{{ $item->product_model_no_snapshot ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $item->color_name_snapshot ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $item->series_snapshot ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $item->quantity }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $item->unit_price, 2, '.', ',') }}</td>
                        <td class="px-4 py-3">{{ number_format((float) $item->line_total, 2, '.', ',') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-4 text-gray-500" colspan="6">لا توجد عناصر لهذا الطلب.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
