# 400-sy.com — Language Toggle Styling

## الهدف

تنسيق رابط تبديل اللغة في الواجهة ليظهر بصريًا بشكل قريب من أزرار البلدان/العملة، مع أيقونة مناسبة ونص اللغة المقابلة.

## الملفات المعدلة

- resources/views/frontend/partials/header.blade.php
- resources/views/frontend/partials/mobile-menu.blade.php
- public/css/styles.css

## ملاحظات

- لا يوجد تعديل على قاعدة البيانات.
- لا توجد migrations.
- يعتمد هذا التعديل على وجود تعديل سابق يجعل زر اللغة يعرض اللغة المعاكسة.
- السكربت يأخذ نسخة احتياطية داخل `_backup/language-toggle-styling-01` قبل التعديل.
