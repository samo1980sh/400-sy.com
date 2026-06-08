# 400-sy.com — Card Product Code Color Sync 01

## الهدف

تحديث كود المنتج داخل بطاقات المنتجات في الواجهة عند تمرير/اختيار دائرة لون داخل البطاقة، بحيث يظهر كود المنتج مدمجًا مع كود اللون المختار.

مثال:

```text
W5R237-A1
W5R237-A2
```

بدل بقاء كود البطاقة على أول لون فقط.

## الملفات المعدلة

```text
resources/views/frontend/partials/product-card.blade.php
resources/views/frontend/partials/product-scripts.blade.php
```

## النطاق

- Frontend فقط.
- لا تعديل على لوحة التحكم.
- لا تعديل على قاعدة البيانات.
- لا تعديل على Excel.
