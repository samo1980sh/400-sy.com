# حزمة تعبئة measurement_group من ملف المنتجات

## المشكلة

عمود `measurement_group` موجود في جدول `products`، لكن الاستيراد لا يقرأ عمود Excel:

زمر وحدة القياس

لذلك يبقى فارغًا.

## ماذا تفعل هذه الحزمة؟

تعدل ملفًا واحدًا فقط:

app/Services/RetailExcelImportService.php

وتضيف:
1. دالة `productMeasurementGroup(array $rows): ?string`
2. حفظ القيمة داخل `Product::updateOrCreate`:
   `measurement_group => $this->productMeasurementGroup($rows)`

## لا تلمس هذه الحزمة

- الفرونت
- الفلتر
- الصور
- الكارت
- الكويك فيو
- قاعدة البيانات
- migrations

## طريقة التطبيق

من جذر المشروع:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\_measurement_group_import\APPLY_measurement_group_import_fix.ps1

php artisan optimize:clear
php -l app\Services\RetailExcelImportService.php
```

ثم أعد استيراد المنتجات.

## فحص بعد الاستيراد

```sql
SELECT model_no, measurement_group
FROM products
WHERE measurement_group IS NOT NULL
  AND TRIM(measurement_group) <> ''
LIMIT 50;
```
