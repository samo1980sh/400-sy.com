# خطوات الاختبار

1. شغّل:

```powershell
php artisan optimize:clear
php -l app\Services\RetailExcelImportService.php
```

2. أعد استيراد المنتجات.

3. افحص من phpMyAdmin:

```sql
SELECT model_no, measurement_group
FROM products
WHERE measurement_group IS NOT NULL
  AND TRIM(measurement_group) <> ''
LIMIT 50;
```

4. إذا ظهرت قيم من عمود Excel `زمر وحدة القياس`، فالتعديل نجح.
