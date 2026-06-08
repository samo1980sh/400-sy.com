# 400 Trader Actions Cleanup 01

هذه الحزمة تنظف أزرار صفحة التجار بعد إضافة قالب الاستيراد.

## الهدف

- الإبقاء على زر **إضافة تاجر** العلوي ضمن مجموعة الأزرار الرئيسية.
- حذف زر **إضافة تاجر** الموجود داخل رأس جدول التجار.
- عدم تعديل منطق الاستيراد أو التصدير أو القالب.

## الملفات المتأثرة

- `app/Filament/Resources/Traders/Tables/TradersTable.php`
- `app/Filament/Resources/Traders/Pages/ListTraders.php`

## الاستخدام

انسخ الحزمة إلى روت المشروع، فك الضغط، ثم شغل:

```powershell
powershell -ExecutionPolicy Bypass -File .\tools\apply-trader-actions-cleanup-01.ps1
```

بعد التجربة احذف ملفات الحزمة والنسخ الاحتياطية المؤقتة قبل التثبيت، أو لا تضفها إلى Git.
