# 400-sy.com — Language Toggle Opposite Label

## الهدف

تعديل زر/رابط تبديل اللغة في الواجهة ليعرض اللغة الأخرى المتاحة، وليس اللغة الحالية.

- عند اللغة العربية يظهر: English
- عند اللغة الإنجليزية يظهر: العربية

## الملفات المعدلة

- resources/views/frontend/partials/header.blade.php
- resources/views/frontend/partials/mobile-menu.blade.php

## ملاحظات

لا يوجد تعديل على قاعدة البيانات ولا توجد migrations.
السكريبت يأخذ نسخة احتياطية داخل `_backup/language-toggle-opposite-01` قبل التعديل.
