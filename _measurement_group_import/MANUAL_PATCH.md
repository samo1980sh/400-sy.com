# التعديل اليدوي

المسار:

app/Services/RetailExcelImportService.php

## 1) أضف هذه الدالة داخل الكلاس

ضعها قرب دوال:

- productBodyFit
- productDropType

```php
private function productMeasurementGroup(array $rows): ?string
{
    foreach ($rows as $row) {
        $value = $this->normalizeText($this->value(
            $row,
            'زمر وحدة القياس',
            'زمرة وحدة القياس',
            'زمر القياس',
            'مجموعة القياس',
            'Measurement Group',
            'measurement_group'
        ));

        if ($value !== '') {
            return $value;
        }
    }

    return null;
}
```

## 2) داخل Product::updateOrCreate

ابحث عن:

```php
'drop_type' => $this->productDropType($first),
```

وأضف بعدها:

```php
'measurement_group' => $this->productMeasurementGroup($rows),
```

إذا ظهر السطر أكثر من مرة داخل الملف، أضف السطر بعد كل ظهور.
