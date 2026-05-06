<div style="width: 100%; margin-top: 16px;">
    <div class="fi-section rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-gray-900">
    @if ($charts->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 px-4 py-6 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400">
            لا توجد صفوف قياس مرتبطة بهذه المجموعة حتى الآن.
        </div>
    @else
        <div style="overflow-x: auto; width: 100%; border: 1px solid rgba(156, 163, 175, 0.25); border-radius: 12px;">
            <table style="min-width: 1400px; width: 100%; border-collapse: collapse; table-layout: auto; font-size: 14px;">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th style="min-width: 100px; padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.25); font-weight: 700;">القياس</th>
                        <th style="min-width: 100px; padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.25); font-weight: 700;">الصدر</th>
                        <th style="min-width: 100px; padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.25); font-weight: 700;">الكتف</th>
                        <th style="min-width: 100px; padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.25); font-weight: 700;">الوسط</th>
                        <th style="min-width: 100px; padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.25); font-weight: 700;">الطول</th>
                        <th style="min-width: 100px; padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.25); font-weight: 700;">الكم</th>
                        <th style="min-width: 100px; padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.25); font-weight: 700;">الياقة</th>
                        <th style="min-width: 110px; padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.25); font-weight: 700;">وسط الرجل</th>
                        <th style="min-width: 100px; padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.25); font-weight: 700;">الخاصرة</th>
                        <th style="min-width: 110px; padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.25); font-weight: 700;">عرض الفخذ</th>
                        <th style="min-width: 110px; padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.25); font-weight: 700;">عرض الرجل</th>
                        <th style="min-width: 110px; padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.25); font-weight: 700;">طول الرجل</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($charts as $chart)
                        <tr>
                            <td style="padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.14); font-weight: 600;">{{ $chart->size_code ?: '-' }}</td>
                            <td style="padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.14);">{{ blank($chart->chest) ? '-' : number_format((float) $chart->chest, 2, '.', ',') }}</td>
                            <td style="padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.14);">{{ blank($chart->shoulder) ? '-' : number_format((float) $chart->shoulder, 2, '.', ',') }}</td>
                            <td style="padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.14);">{{ blank($chart->waist) ? '-' : number_format((float) $chart->waist, 2, '.', ',') }}</td>
                            <td style="padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.14);">{{ blank($chart->length) ? '-' : number_format((float) $chart->length, 2, '.', ',') }}</td>
                            <td style="padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.14);">{{ blank($chart->sleeve) ? '-' : number_format((float) $chart->sleeve, 2, '.', ',') }}</td>
                            <td style="padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.14);">{{ blank($chart->collar) ? '-' : number_format((float) $chart->collar, 2, '.', ',') }}</td>
                            <td style="padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.14);">{{ blank($chart->inside_leg) ? '-' : number_format((float) $chart->inside_leg, 2, '.', ',') }}</td>
                            <td style="padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.14);">{{ blank($chart->waistline) ? '-' : number_format((float) $chart->waistline, 2, '.', ',') }}</td>
                            <td style="padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.14);">{{ blank($chart->thigh_width) ? '-' : number_format((float) $chart->thigh_width, 2, '.', ',') }}</td>
                            <td style="padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.14);">{{ blank($chart->leg_width) ? '-' : number_format((float) $chart->leg_width, 2, '.', ',') }}</td>
                            <td style="padding: 12px 16px; text-align: center; border-bottom: 1px solid rgba(156, 163, 175, 0.14);">{{ blank($chart->leg_length) ? '-' : number_format((float) $chart->leg_length, 2, '.', ',') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
</div>
