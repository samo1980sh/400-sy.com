<div class="space-y-4">
    @if ($history->isEmpty())
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
            لا يوجد سجل حالات لهذا الطلب حتى الآن.
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200">
            <table class="w-full text-right text-sm">
                <thead class="bg-gray-50 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 font-medium">الوقت</th>
                        <th class="px-4 py-3 font-medium">من</th>
                        <th class="px-4 py-3 font-medium">إلى</th>
                        <th class="px-4 py-3 font-medium">المستخدم</th>
                        <th class="px-4 py-3 font-medium">ملاحظة</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach ($history as $row)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ optional($row->created_at)->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $row->from_status ?? $row->from_payment_status ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $row->to_status ?? $row->to_payment_status ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $row->changedBy?->name ?? 'النظام' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $row->note ?? '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
