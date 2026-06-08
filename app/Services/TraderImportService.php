<?php

namespace App\Services;

use App\Models\Trader;
use App\Models\WholesaleCustomerGroup;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

class TraderImportService
{
    /**
     * @return array{created:int,updated:int,skipped:int,groups_created:int,errors:array<int,string>}
     */
    public function import(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('ملف التجار غير موجود أو غير قابل للقراءة.');
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, false, false, false);

        if ($rows === [] || ! is_array($rows[0] ?? null)) {
            throw new RuntimeException('ملف التجار فارغ.');
        }

        $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), array_values(array_shift($rows)));

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $groupsCreated = 0;
        $errors = [];

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2;
            $mapped = $this->mapRow($headers, $row);

            $name = $this->value($mapped, ['اسم التاجر', 'الاسم', 'name', 'trader name']);
            $mobile = $this->value($mapped, ['رقم الموبايل', 'الموبايل', 'الهاتف', 'الجوال', 'phone', 'mobile']);

            if ($name === '' || $mobile === '') {
                $skipped++;
                $errors[] = "السطر {$rowNumber}: اسم التاجر ورقم الموبايل مطلوبان.";
                continue;
            }

            $groupName = $this->value($mapped, ['فئة التاجر', 'الفئة', 'group', 'customer group', 'wholesale group']);
            $groupId = null;

            if ($groupName !== '') {
                [$groupId, $wasCreated] = $this->resolveGroup($groupName);

                if ($wasCreated) {
                    $groupsCreated++;
                }
            }

            $payload = [
                'account_no' => $this->nullable($this->value($mapped, ['رقم الحساب', 'حساب', 'account no', 'account_no', 'account'])),
                'name' => $name,
                'mobile' => $mobile,
                'secondary_mobile' => $this->nullable($this->value($mapped, ['رقم موبايل آخر', 'موبايل آخر', 'هاتف آخر', 'secondary mobile', 'secondary_mobile'])),
                'email' => $this->nullable($this->value($mapped, ['البريد الإلكتروني', 'الايميل', 'البريد', 'email'])),
                'wholesale_customer_group_id' => $groupId,
                'city' => $this->nullable($this->value($mapped, ['المدينة', 'city'])),
                'area' => $this->nullable($this->value($mapped, ['المنطقة', 'area'])),
                'address_line' => $this->nullable($this->value($mapped, ['العنوان', 'address', 'address line', 'address_line'])),
                'status' => $this->status($this->value($mapped, ['الحالة', 'status'])),
                'notes' => $this->nullable($this->value($mapped, ['ملاحظات', 'notes'])),
            ];

            $password = $this->value($mapped, ['كلمة المرور', 'password']);

            if ($password !== '') {
                $payload['password'] = $password;
            }

            try {
                $trader = $this->findTrader($payload);

                if ($trader) {
                    $trader->update($payload);
                    $updated++;
                } else {
                    Trader::query()->create($payload);
                    $created++;
                }
            } catch (Throwable $exception) {
                report($exception);

                $skipped++;
                $errors[] = "السطر {$rowNumber}: فشل حفظ التاجر {$name} - {$exception->getMessage()}";
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'groups_created' => $groupsCreated,
            'errors' => array_slice($errors, 0, 10),
        ];
    }

    /**
     * @param array<int,string> $headers
     * @param array<int,mixed> $row
     * @return array<string,mixed>
     */
    protected function mapRow(array $headers, array $row): array
    {
        $mapped = [];

        foreach ($row as $index => $value) {
            $header = $headers[$index] ?? '';

            if ($header === '') {
                continue;
            }

            $mapped[$header] = $value;
        }

        return $mapped;
    }

    /**
     * @param array<string,mixed> $row
     * @param array<int,string> $aliases
     */
    protected function value(array $row, array $aliases): string
    {
        foreach ($aliases as $alias) {
            $key = $this->normalizeHeader($alias);

            if (array_key_exists($key, $row)) {
                return trim((string) $row[$key]);
            }
        }

        return '';
    }

    protected function nullable(string $value): ?string
    {
        return $value === '' ? null : $value;
    }

    protected function status(string $value): string
    {
        $normalized = $this->normalizeHeader($value);

        return match ($normalized) {
            'inactive', 'غيرفعال', 'غيرنشط', 'موقوف' => 'inactive',
            default => 'active',
        };
    }

    /**
     * @return array{0:int,1:bool}
     */
    protected function resolveGroup(string $name): array
    {
        $normalized = $this->normalizeText($name);

        $group = WholesaleCustomerGroup::query()
            ->whereRaw('REPLACE(TRIM(name_ar), " ", "") = ?', [str_replace(' ', '', $normalized)])
            ->orWhereRaw('LOWER(TRIM(name_en)) = ?', [mb_strtolower($normalized)])
            ->orWhereRaw('LOWER(TRIM(code)) = ?', [mb_strtolower($normalized)])
            ->first();

        if ($group) {
            return [(int) $group->getKey(), false];
        }

        $group = WholesaleCustomerGroup::query()->create([
            'name_ar' => $normalized,
            'name_en' => $normalized,
            'code' => $this->groupCode($normalized),
            'status' => 'active',
            'sort_order' => ((int) WholesaleCustomerGroup::query()->max('sort_order')) + 1,
        ]);

        return [(int) $group->getKey(), true];
    }

    /**
     * @param array<string,mixed> $payload
     */
    protected function findTrader(array $payload): ?Trader
    {
        foreach (['account_no', 'email', 'mobile'] as $field) {
            $value = $payload[$field] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            $trader = Trader::query()->where($field, $value)->first();

            if ($trader) {
                return $trader;
            }
        }

        return null;
    }

    protected function normalizeHeader(string $value): string
    {
        $value = $this->normalizeText($value);
        $value = mb_strtolower($value);
        $value = str_replace(['أ', 'إ', 'آ'], 'ا', $value);
        $value = str_replace('ة', 'ه', $value);
        $value = preg_replace('/\s+/u', '', $value) ?? $value;
        $value = preg_replace('/[^\p{L}\p{N}_]/u', '', $value) ?? $value;

        return $value;
    }

    protected function normalizeText(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value;
    }

    protected function groupCode(string $value): string
    {
        $code = mb_strtolower($value);
        $code = str_replace(['أ', 'إ', 'آ'], 'ا', $code);
        $code = preg_replace('/\s+/u', '-', trim($code)) ?? $code;
        $code = preg_replace('/[^\p{L}\p{N}\-]+/u', '', $code) ?? $code;

        return $code ?: 'group-'.time();
    }
}
