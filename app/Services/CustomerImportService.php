<?php

namespace App\Services;

use App\Models\Customer;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;
use Throwable;

class CustomerImportService
{
    /**
     * @var array<int, string>
     */
    private const ACCOUNT_ALIASES = [
        'رقم الحساب',
        'رقم حساب العميل',
        'account no',
        'account_no',
        'account number',
        'account',
    ];

    /**
     * @var array<int, string>
     */
    private const NAME_ALIASES = [
        'الاسم الكامل',
        'اسم العميل',
        'اسم الزبون',
        'الاسم',
        'full name',
        'customer name',
        'name',
    ];

    /**
     * @var array<int, string>
     */
    private const MOBILE_ALIASES = [
        'رقم الموبايل',
        'الموبايل',
        'الهاتف',
        'الجوال',
        'phone',
        'mobile',
    ];

    /**
     * @return array{created:int,updated:int,skipped:int,errors:array<int,string>}
     */
    public function import(string $path): array
    {
        if (! is_file($path)) {
            throw new RuntimeException('ملف الزبائن غير موجود أو غير قابل للقراءة.');
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        try {
            $headers = $this->headers($sheet);
            $this->validateRequiredHeaders($headers);

            $created = 0;
            $updated = 0;
            $skipped = 0;
            $errors = [];
            $highestRow = $sheet->getHighestDataRow();

            for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
                $mapped = $this->mapRow($sheet, $headers, $rowNumber);

                if ($this->rowIsEmpty($mapped)) {
                    continue;
                }

                try {
                    $result = $this->importRow($mapped);

                    if ($result === 'created') {
                        $created++;
                    } else {
                        $updated++;
                    }
                } catch (Throwable $exception) {
                    report($exception);

                    $skipped++;
                    $errors[] = "السطر {$rowNumber}: {$exception->getMessage()}";
                }
            }

            return [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => array_slice($errors, 0, 12),
            ];
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * @param array<string, array{formatted:string,raw:mixed,cell:Cell}> $row
     */
    protected function importRow(array $row): string
    {
        $accountNo = $this->value($row, self::ACCOUNT_ALIASES);
        $name = $this->normalizeText($this->value($row, self::NAME_ALIASES));
        $mobile = $this->normalizeMobile($this->value($row, self::MOBILE_ALIASES));

        if ($accountNo === '' || $name === '' || $mobile === '') {
            throw new RuntimeException('رقم الحساب والاسم الكامل ورقم الموبايل حقول مطلوبة.');
        }

        $emailAliases = [
            'البريد الإلكتروني',
            'البريد الالكتروني',
            'الايميل',
            'البريد',
            'email',
        ];

        $emailProvided = $this->hasValue($row, $emailAliases);
        $email = $emailProvided
            ? $this->normalizeEmail($this->value($row, $emailAliases))
            : null;

        if ($emailProvided && ($email === null || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            throw new RuntimeException('صيغة البريد الإلكتروني غير صحيحة.');
        }

        $payload = [
            'account_no' => $accountNo,
            'name' => $name,
            'mobile' => $mobile,
        ];

        $this->putOptional($payload, 'secondary_mobile', $row, [
            'الموبايل الثانوي',
            'رقم موبايل آخر',
            'موبايل آخر',
            'هاتف آخر',
            'secondary mobile',
            'secondary_mobile',
        ], fn (string $value): ?string => $this->nullableMobile($value));

        if ($emailProvided) {
            $payload['email'] = $email;
        }

        $this->putOptional($payload, 'gender', $row, ['الجنس', 'gender'], fn (string $value): ?string => $this->gender($value));
        $this->putOptionalDate($payload, 'birth_date', $row, ['تاريخ الميلاد', 'birth date', 'birth_date']);
        $this->putOptional($payload, 'nationality', $row, ['الجنسية', 'nationality'], fn (string $value): ?string => $this->nullableText($value));
        $this->putOptional($payload, 'city', $row, ['المدينة', 'city'], fn (string $value): ?string => $this->nullableText($value));
        $this->putOptional($payload, 'area', $row, ['المنطقة', 'area'], fn (string $value): ?string => $this->nullableText($value));
        $this->putOptional($payload, 'job_title', $row, ['المهنة', 'الوظيفة', 'job title', 'job_title'], fn (string $value): ?string => $this->nullableText($value));
        $this->putOptional($payload, 'marital_status', $row, ['الحالة الاجتماعية', 'marital status', 'marital_status'], fn (string $value): ?string => $this->maritalStatus($value));
        $this->putOptional($payload, 'status', $row, ['الحالة', 'status'], fn (string $value): string => $this->status($value));
        $this->putOptional($payload, 'notes', $row, ['ملاحظات', 'notes'], fn (string $value): ?string => $this->nullableText($value));

        return DB::transaction(function () use ($payload, $accountNo, $mobile, $email, $emailProvided): string {
            $customer = Customer::query()
                ->where('account_no', $accountNo)
                ->first();

            $mobileConflict = Customer::query()
                ->where('mobile', $mobile)
                ->when(
                    $customer,
                    fn ($query) => $query->where('id', '<>', $customer->getKey())
                )
                ->first();

            if ($mobileConflict) {
                throw new RuntimeException(
                    "رقم الموبايل مستخدم مسبقًا للحساب {$mobileConflict->account_no}."
                );
            }

            if ($emailProvided && $email !== null) {
                $emailConflict = Customer::query()
                    ->where('email', $email)
                    ->when(
                        $customer,
                        fn ($query) => $query->where('id', '<>', $customer->getKey())
                    )
                    ->first();

                if ($emailConflict) {
                    throw new RuntimeException(
                        "البريد الإلكتروني مستخدم مسبقًا للحساب {$emailConflict->account_no}."
                    );
                }
            }

            if ($customer) {
                $customer->update($payload);

                return 'updated';
            }

            $payload['status'] = $payload['status'] ?? 'active';

            Customer::query()->create($payload);

            return 'created';
        });
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, array{formatted:string,raw:mixed,cell:Cell}> $row
     * @param array<int, string> $aliases
     */
    protected function putOptional(
        array &$payload,
        string $field,
        array $row,
        array $aliases,
        callable $transform,
    ): void {
        if (! $this->hasValue($row, $aliases)) {
            return;
        }

        $payload[$field] = $transform($this->value($row, $aliases));
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, array{formatted:string,raw:mixed,cell:Cell}> $row
     * @param array<int, string> $aliases
     */
    protected function putOptionalDate(array &$payload, string $field, array $row, array $aliases): void
    {
        if (! $this->hasValue($row, $aliases)) {
            return;
        }

        $payload[$field] = $this->date($row, $aliases);
    }

    /**
     * @return array<int, string>
     */
    protected function headers(Worksheet $sheet): array
    {
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        $headers = [];

        for ($columnIndex = 1; $columnIndex <= $highestColumnIndex; $columnIndex++) {
            $coordinate = Coordinate::stringFromColumnIndex($columnIndex) . '1';
            $header = $this->normalizeHeader((string) $sheet->getCell($coordinate)->getFormattedValue());

            if ($header !== '') {
                $headers[$columnIndex] = $header;
            }
        }

        if ($headers === []) {
            throw new RuntimeException('ملف الزبائن لا يحتوي على عناوين أعمدة صالحة.');
        }

        return $headers;
    }

    /**
     * @param array<int, string> $headers
     */
    protected function validateRequiredHeaders(array $headers): void
    {
        $missing = [];

        if (! $this->hasHeader($headers, self::ACCOUNT_ALIASES)) {
            $missing[] = 'رقم الحساب';
        }

        if (! $this->hasHeader($headers, self::NAME_ALIASES)) {
            $missing[] = 'الاسم الكامل';
        }

        if (! $this->hasHeader($headers, self::MOBILE_ALIASES)) {
            $missing[] = 'رقم الموبايل';
        }

        if ($missing !== []) {
            throw new RuntimeException('الأعمدة المطلوبة غير موجودة: ' . implode('، ', $missing));
        }
    }

    /**
     * @param array<int, string> $headers
     * @param array<int, string> $aliases
     */
    protected function hasHeader(array $headers, array $aliases): bool
    {
        $normalizedAliases = array_map(fn (string $alias): string => $this->normalizeHeader($alias), $aliases);

        foreach ($headers as $header) {
            if (in_array($header, $normalizedAliases, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, string> $headers
     * @return array<string, array{formatted:string,raw:mixed,cell:Cell}>
     */
    protected function mapRow(Worksheet $sheet, array $headers, int $rowNumber): array
    {
        $mapped = [];

        foreach ($headers as $columnIndex => $header) {
            $coordinate = Coordinate::stringFromColumnIndex($columnIndex) . $rowNumber;
            $cell = $sheet->getCell($coordinate);

            $mapped[$header] = [
                'formatted' => trim((string) $cell->getFormattedValue()),
                'raw' => $cell->getValue(),
                'cell' => $cell,
            ];
        }

        return $mapped;
    }

    /**
     * @param array<string, array{formatted:string,raw:mixed,cell:Cell}> $row
     */
    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell['formatted'] !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, array{formatted:string,raw:mixed,cell:Cell}> $row
     * @param array<int, string> $aliases
     */
    protected function hasValue(array $row, array $aliases): bool
    {
        $cell = $this->cell($row, $aliases);

        return $cell !== null && trim($cell['formatted']) !== '';
    }

    /**
     * @param array<string, array{formatted:string,raw:mixed,cell:Cell}> $row
     * @param array<int, string> $aliases
     */
    protected function value(array $row, array $aliases): string
    {
        $cell = $this->cell($row, $aliases);

        return $cell['formatted'] ?? '';
    }

    /**
     * @param array<string, array{formatted:string,raw:mixed,cell:Cell}> $row
     * @param array<int, string> $aliases
     * @return array{formatted:string,raw:mixed,cell:Cell}|null
     */
    protected function cell(array $row, array $aliases): ?array
    {
        foreach ($aliases as $alias) {
            $key = $this->normalizeHeader($alias);

            if (array_key_exists($key, $row)) {
                return $row[$key];
            }
        }

        return null;
    }

    /**
     * @param array<string, array{formatted:string,raw:mixed,cell:Cell}> $row
     * @param array<int, string> $aliases
     */
    protected function date(array $row, array $aliases): ?string
    {
        $cellData = $this->cell($row, $aliases);

        if ($cellData === null || $cellData['formatted'] === '') {
            return null;
        }

        $raw = $cellData['raw'];
        $cell = $cellData['cell'];

        if (is_numeric($raw) && ExcelDate::isDateTime($cell)) {
            return ExcelDate::excelToDateTimeObject((float) $raw)->format('Y-m-d');
        }

        $value = $this->normalizeDigits($cellData['formatted']);

        foreach (['Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        throw new RuntimeException("صيغة تاريخ الميلاد غير صحيحة: {$cellData['formatted']}. استخدم YYYY-MM-DD.");
    }

    protected function gender(string $value): ?string
    {
        return match ($this->normalizeHeader($value)) {
            'male', 'ذكر', 'رجل' => 'male',
            'female', 'انثى', 'امراه' => 'female',
            default => throw new RuntimeException("قيمة الجنس غير صحيحة: {$value}. استخدم ذكر أو أنثى."),
        };
    }

    protected function maritalStatus(string $value): ?string
    {
        return match ($this->normalizeHeader($value)) {
            'single', 'اعزب', 'عزباء' => 'single',
            'married', 'متزوج', 'متزوجه' => 'married',
            'divorced', 'مطلق', 'مطلقه' => 'divorced',
            'widowed', 'ارمل', 'ارمله' => 'widowed',
            default => throw new RuntimeException("قيمة الحالة الاجتماعية غير صحيحة: {$value}."),
        };
    }

    protected function status(string $value): string
    {
        return match ($this->normalizeHeader($value)) {
            'active', 'فعال', 'نشط' => 'active',
            'inactive', 'غيرفعال', 'غيرنشط', 'موقوف' => 'inactive',
            default => throw new RuntimeException("قيمة الحالة غير صحيحة: {$value}. استخدم فعال أو غير فعال."),
        };
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

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    protected function normalizeDigits(string $value): string
    {
        return strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }

    protected function normalizeMobile(string $value): string
    {
        $value = $this->normalizeDigits($value);

        return preg_replace('/[\s\-()]+/u', '', trim($value)) ?? '';
    }

    protected function nullableMobile(string $value): ?string
    {
        $value = $this->normalizeMobile($value);

        return $value === '' ? null : $value;
    }

    protected function normalizeEmail(string $value): ?string
    {
        $value = mb_strtolower(trim($value));

        return $value === '' ? null : $value;
    }

    protected function nullableText(string $value): ?string
    {
        $value = $this->normalizeText($value);

        return $value === '' ? null : $value;
    }
}
