# 400-sy.com - Trader Import Template Button

This package adds a trader import template download button to the Traders Filament list page.

## Added files

- `app/Services/TraderTemplateService.php`

## Modified files

- `app/Filament/Resources/Traders/Pages/ListTraders.php`

## Behavior

The Traders page header actions now include:

1. `قالب الاستيراد`
2. `استيراد`
3. `تصدير`
4. Create action

The template contains these columns:

- رقم الحساب
- اسم التاجر
- رقم الموبايل
- رقم موبايل آخر
- البريد الإلكتروني
- فئة التاجر
- المدينة
- المنطقة
- العنوان
- الحالة
- كلمة المرور
- ملاحظات

The import logic remains unchanged.
