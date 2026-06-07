<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CategoryImportService
{
    /**
     * @return array{created:int,updated:int,skipped:int,errors:array<int,string>}
     */
    public function import(string|array|null $uploadedFile, ?int $fallbackParentId = null): array
    {
        $uploadedPath = $this->resolveUploadedPath($uploadedFile);
        $spreadsheet = IOFactory::load($uploadedPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            throw new RuntimeException('ملف الاستيراد فارغ أو لا يحتوي على بيانات كافية.');
        }

        $headerRow = array_shift($rows);
        $headers = $this->headers($headerRow ?? []);

        if (! in_array('title_ar', $headers, true)) {
            throw new RuntimeException('ملف الاستيراد يجب أن يحتوي على عمود الاسم بالعربي.');
        }

        $summary = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        DB::transaction(function () use ($rows, $headers, $fallbackParentId, &$summary): void {
            foreach ($rows as $index => $row) {
                $excelRowNumber = $index + 2;

                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                try {
                    $data = $this->rowData($headers, $row);
                    $titleAr = $this->string($data['title_ar'] ?? null);

                    if ($titleAr === '') {
                        $summary['skipped']++;
                        $summary['errors'][] = "السطر {$excelRowNumber}: الاسم بالعربي مطلوب.";
                        continue;
                    }

                    $parentId = $this->resolveParentId($data, $fallbackParentId);
                    $category = $this->findExistingCategory($data, $titleAr, $parentId);
                    $isRoot = blank($parentId);

                    $payload = [
                        'parent_id' => $parentId,
                        'title_ar' => $titleAr,
                        'title_en' => $this->string($data['title_en'] ?? null) ?: $titleAr,
                        'sort_order' => $this->integer($data['sort_order'] ?? null) ?: (((int) Category::query()->max('sort_order')) + 1),
                        'banner' => $this->string($data['banner'] ?? null) ?: null,
                    ];

                    $slug = $this->string($data['slug'] ?? null);
                    if ($slug !== '') {
                        $payload['slug'] = $slug;
                    }

                    if ($isRoot) {
                        $payload['image'] = $this->string($data['image'] ?? null) ?: null;
                        $payload['show_in_home'] = $this->boolean($data['show_in_home'] ?? null);
                    } else {
                        $payload['image'] = null;
                        $payload['show_in_home'] = false;
                    }

                    if ($category) {
                        if ($slug === '' && filled($category->slug)) {
                            unset($payload['slug']);
                        }

                        $category->update($payload);
                        $summary['updated']++;
                    } else {
                        Category::query()->create($payload);
                        $summary['created']++;
                    }
                } catch (\Throwable $exception) {
                    $summary['skipped']++;
                    $summary['errors'][] = "السطر {$excelRowNumber}: " . $exception->getMessage();
                }
            }
        });

        $this->deleteUploadedFile($uploadedFile);

        return $summary;
    }

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('قالب التصنيفات');

        $rows = [
            [
                'رقم التصنيف',
                'معرف التصنيف الأب',
                'Slug التصنيف الأب',
                'التصنيف الأب',
                'الاسم بالعربي',
                'الاسم بالانكليزي',
                'الرابط / Slug',
                'صورة بطاقة التصنيف',
                'بانر التصنيف',
                'يظهر في الصفحة الرئيسية',
                'الترتيب',
            ],
            [
                '',
                '',
                '',
                '',
                'رجالي',
                'Men',
                'men',
                'categories/images/men.webp',
                'categories/banners/men.webp',
                'نعم',
                '1',
            ],
            [
                '',
                '',
                'men',
                'رجالي',
                'جاكيتات',
                'Jackets',
                'men-jackets',
                '',
                'categories/banners/men-jackets.webp',
                'لا',
                '2',
            ],
        ];

        $this->fillSheet($sheet, $rows);
        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'categories-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function resolveParentId(array $row, ?int $fallbackParentId = null): ?int
    {
        $parentId = $this->integer($row['parent_id'] ?? null);
        if ($parentId && Category::query()->whereKey($parentId)->exists()) {
            return $parentId;
        }

        $parentSlug = $this->string($row['parent_slug'] ?? null);
        if ($parentSlug !== '') {
            $category = Category::query()->where('slug', $parentSlug)->first();
            if ($category) {
                return (int) $category->getKey();
            }
        }

        $parentTitle = $this->string($row['parent_title'] ?? null);
        if ($parentTitle !== '') {
            $category = Category::query()
                ->where('title_ar', $parentTitle)
                ->orWhere('title_en', $parentTitle)
                ->orWhere('slug', $parentTitle)
                ->orderBy('parent_id')
                ->orderBy('sort_order')
                ->first();

            if ($category) {
                return (int) $category->getKey();
            }
        }

        return $fallbackParentId ?: null;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function findExistingCategory(array $row, string $titleAr, ?int $parentId = null): ?Category
    {
        $id = $this->integer($row['id'] ?? null);
        if ($id) {
            $category = Category::query()->find($id);
            if ($category) {
                return $category;
            }
        }

        $slug = $this->string($row['slug'] ?? null);
        if ($slug !== '') {
            $category = Category::query()->where('slug', $slug)->first();
            if ($category) {
                return $category;
            }
        }

        return Category::query()
            ->where('title_ar', $titleAr)
            ->where('parent_id', $parentId)
            ->first();
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, string>
     */
    protected function headers(array $row): array
    {
        $headers = [];

        foreach ($row as $column => $label) {
            $key = $this->headerKey($label);
            if ($key !== null) {
                $headers[$column] = $key;
            }
        }

        return $headers;
    }

    protected function headerKey(mixed $label): ?string
    {
        $label = mb_strtolower(trim((string) $label));
        $label = str_replace(['ـ', '\\', '/', '-', '_'], [' ', ' ', ' ', ' ', ' '], $label);
        $label = preg_replace('/\s+/u', ' ', $label) ?: $label;

        return match ($label) {
            'id', 'رقم التصنيف', 'معرف التصنيف', 'category id' => 'id',
            'parent id', 'parent_id', 'معرف التصنيف الأب', 'معرف الاب', 'معرف الأب' => 'parent_id',
            'parent slug', 'parent_slug', 'slug التصنيف الأب', 'رابط التصنيف الأب' => 'parent_slug',
            'parent', 'التصنيف الأب', 'التصنيف الاب', 'اسم التصنيف الأب', 'اسم الاب', 'اسم الأب' => 'parent_title',
            'title ar', 'title_ar', 'name ar', 'name_ar', 'الاسم بالعربي', 'العنوان بالعربية', 'العنوان العربي' => 'title_ar',
            'title en', 'title_en', 'name en', 'name_en', 'الاسم بالانكليزي', 'العنوان بالانكليزية', 'العنوان الانكليزي', 'الاسم بالإنكليزي', 'العنوان بالإنكليزية' => 'title_en',
            'slug', 'الرابط slug', 'الرابط', 'الرابط / slug', 'رابط التصنيف' => 'slug',
            'image', 'صورة بطاقة التصنيف', 'صورة التصنيف', 'الصورة' => 'image',
            'banner', 'بانر التصنيف', 'البانر' => 'banner',
            'show in home', 'show_in_home', 'يظهر في الصفحة الرئيسية', 'الصفحة الرئيسية' => 'show_in_home',
            'sort order', 'sort_order', 'الترتيب' => 'sort_order',
            default => null,
        };
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    protected function rowData(array $headers, array $row): array
    {
        $data = [];

        foreach ($headers as $column => $key) {
            $data[$key] = $row[$column] ?? null;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function rowIsEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->string($value) !== '') {
                return false;
            }
        }

        return true;
    }

    protected function resolveUploadedPath(string|array|null $uploadedFile): string
    {
        $path = $this->firstUploadedPath($uploadedFile);

        if (! $path) {
            throw new RuntimeException('لم يتم اختيار ملف Excel للاستيراد.');
        }

        if (is_file($path)) {
            return $path;
        }

        $disk = Storage::disk('local');
        if ($disk->exists($path)) {
            return $disk->path($path);
        }

        $candidatePaths = [
            storage_path('app/' . ltrim($path, '/')),
            storage_path('app/private/' . ltrim($path, '/')),
        ];

        foreach ($candidatePaths as $candidatePath) {
            if (is_file($candidatePath)) {
                return $candidatePath;
            }
        }

        throw new RuntimeException('تعذر الوصول إلى ملف الاستيراد: ' . $path);
    }

    protected function firstUploadedPath(string|array|null $uploadedFile): ?string
    {
        if (is_string($uploadedFile)) {
            return $uploadedFile;
        }

        if (is_array($uploadedFile)) {
            foreach ($uploadedFile as $value) {
                $path = $this->firstUploadedPath($value);
                if ($path) {
                    return $path;
                }
            }
        }

        return null;
    }

    protected function deleteUploadedFile(string|array|null $uploadedFile): void
    {
        $path = $this->firstUploadedPath($uploadedFile);

        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    protected function string(mixed $value): string
    {
        return trim((string) $value);
    }

    protected function integer(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        return null;
    }

    protected function boolean(mixed $value): bool
    {
        $value = mb_strtolower($this->string($value));

        return in_array($value, ['1', 'true', 'yes', 'y', 'نعم', 'صح', 'مفعل', 'فعال'], true);
    }

    protected function fillSheet(Worksheet $sheet, array $rows): void
    {
        $sheet->fromArray($rows, null, 'A1');
        $sheet->freezePane('A2');
        $sheet->getStyle('1:1')->getFont()->setBold(true);

        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        foreach (range(1, $highestColumnIndex) as $columnIndex) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($columnIndex))->setAutoSize(true);
        }
    }
}
