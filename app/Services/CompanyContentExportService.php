<?php

namespace App\Services;

use App\Models\CompanyHeaderImage;
use App\Models\CompanyNewsItem;
use App\Models\CompanyNewsTickerItem;
use App\Models\CompanyPage;
use App\Models\CompanySocialLink;
use App\Models\ContactInfoSetting;
use App\Models\InternalPageHeader;
use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyContentExportService
{
    public function download(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        $this->buildCompanyPagesSheet($spreadsheet->getActiveSheet());
        $this->buildHeaderImagesSheet($spreadsheet->createSheet());
        $this->buildNewsItemsSheet($spreadsheet->createSheet());
        $this->buildNewsTickerSheet($spreadsheet->createSheet());
        $this->buildSocialLinksSheet($spreadsheet->createSheet());
        $this->buildContactInfoSheet($spreadsheet->createSheet());
        $this->buildInternalHeadersSheet($spreadsheet->createSheet());

        $filename = 'company-content-export-' . now()->format('Y-m-d-His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function buildCompanyPagesSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('صفحات الشركة');

        $rows = CompanyPage::query()
            ->latest('id')
            ->get()
            ->map(fn (CompanyPage $page): array => [
                $page->getKey(),
                $this->value($page, ['slug', 'key', 'type']),
                $this->value($page, ['title_ar', 'name_ar', 'headline_ar']),
                $this->value($page, ['title_en', 'name_en', 'headline_en']),
                $this->value($page, ['content_ar', 'body_ar', 'description_ar']),
                $this->value($page, ['content_en', 'body_en', 'description_en']),
                $this->value($page, ['image', 'main_image', 'cover_image']),
                $this->value($page, ['sort_order']),
                $this->statusText($this->value($page, ['status', 'is_active'])),
                $this->dateTime($this->value($page, ['updated_at'])),
            ])
            ->all();

        $this->fillSheet($sheet, [
            'المعرف',
            'المفتاح / النوع',
            'العنوان بالعربي',
            'العنوان بالانكليزي',
            'المحتوى بالعربي',
            'المحتوى بالانكليزي',
            'الصورة',
            'الترتيب',
            'الحالة',
            'تاريخ التحديث',
        ], $rows);
    }

    private function buildHeaderImagesSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('صور الهيدر');

        $rows = CompanyHeaderImage::query()
            ->orderBy('sort_order')
            ->latest('id')
            ->get()
            ->map(fn (CompanyHeaderImage $item): array => [
                $this->value($item, ['title_ar', 'headline_ar']),
                $this->value($item, ['title_en', 'headline_en']),
                $this->value($item, ['text_ar', 'description_ar', 'content_ar']),
                $this->value($item, ['text_en', 'description_en', 'content_en']),
                $this->value($item, ['image', 'video', 'media']),
                $this->value($item, ['button_url', 'link_url', 'url']),
                $this->value($item, ['button_text_ar', 'cta_text_ar']),
                $this->value($item, ['button_text_en', 'cta_text_en']),
                $this->value($item, ['sort_order']),
                $this->statusText($this->value($item, ['status', 'is_active'])),
                $this->dateTime($this->value($item, ['created_at'])),
            ])
            ->all();

        $this->fillSheet($sheet, [
            'العنوان بالعربي',
            'العنوان بالانكليزي',
            'النص بالعربي',
            'النص بالانكليزي',
            'الصورة',
            'رابط الزر',
            'نص الزر بالعربي',
            'نص الزر بالانكليزي',
            'الترتيب',
            'الحالة',
            'تاريخ الإنشاء',
        ], $rows);
    }

    private function buildNewsItemsSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('أخبار الشركة');

        $rows = CompanyNewsItem::query()
            ->latest('id')
            ->get()
            ->map(fn (CompanyNewsItem $item): array => [
                $this->value($item, ['title_ar', 'headline_ar']),
                $this->value($item, ['title_en', 'headline_en']),
                $this->value($item, ['excerpt_ar', 'summary_ar']),
                $this->value($item, ['excerpt_en', 'summary_en']),
                $this->value($item, ['content_ar', 'body_ar']),
                $this->value($item, ['content_en', 'body_en']),
                $this->value($item, ['main_image', 'image']),
                $this->boolText($this->value($item, ['status', 'is_published']) === 'active' || $this->value($item, ['is_published']) === '1'),
                $this->boolText(in_array($this->value($item, ['type', 'is_featured', 'featured']), ['featured', '1'], true)),
                $this->dateTime($this->value($item, ['event_date', 'published_at'])),
                $this->dateTime($this->value($item, ['created_at'])),
            ])
            ->all();

        $this->fillSheet($sheet, [
            'العنوان بالعربي',
            'العنوان بالانكليزي',
            'الملخص بالعربي',
            'الملخص بالانكليزي',
            'المحتوى بالعربي',
            'المحتوى بالانكليزي',
            'الصورة',
            'منشور',
            'مميز',
            'تاريخ النشر',
            'تاريخ الإنشاء',
        ], $rows);
    }

    private function buildNewsTickerSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('الشريط الإخباري');

        $rows = CompanyNewsTickerItem::query()
            ->orderBy('sort_order')
            ->latest('id')
            ->get()
            ->map(fn (CompanyNewsTickerItem $item): array => [
                $this->value($item, ['text_ar']),
                $this->value($item, ['text_en']),
                $this->value($item, ['link_url', 'url']),
                $this->value($item, ['sort_order']),
                $this->statusText($this->value($item, ['status', 'is_active'])),
                $this->dateTime($this->value($item, ['created_at'])),
            ])
            ->all();

        $this->fillSheet($sheet, [
            'النص بالعربي',
            'النص بالانكليزي',
            'الرابط',
            'الترتيب',
            'الحالة',
            'تاريخ الإنشاء',
        ], $rows);
    }

    private function buildSocialLinksSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('روابط التواصل');

        $rows = CompanySocialLink::query()
            ->orderBy('sort_order')
            ->latest('id')
            ->get()
            ->map(fn (CompanySocialLink $item): array => [
                $this->value($item, ['platform_key', 'platform']),
                $this->value($item, ['url', 'link_url']),
                $this->value($item, ['icon']),
                $this->value($item, ['sort_order']),
                $this->statusText($this->value($item, ['status', 'is_active'])),
                $this->dateTime($this->value($item, ['created_at'])),
            ])
            ->all();

        $this->fillSheet($sheet, [
            'المنصة',
            'الرابط',
            'الأيقونة',
            'الترتيب',
            'الحالة',
            'تاريخ الإنشاء',
        ], $rows);
    }

    private function buildContactInfoSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('معلومات التواصل');

        $rows = ContactInfoSetting::query()
            ->latest('id')
            ->get()
            ->map(fn (ContactInfoSetting $item): array => [
                $this->value($item, ['phone']),
                $this->value($item, ['mobile']),
                $this->value($item, ['whatsapp']),
                $this->value($item, ['email']),
                $this->value($item, ['address_ar']),
                $this->value($item, ['address_en']),
                $this->value($item, ['map_url', 'map']),
                $this->value($item, ['working_hours_ar']),
                $this->value($item, ['working_hours_en']),
                $this->dateTime($this->value($item, ['updated_at'])),
            ])
            ->all();

        $this->fillSheet($sheet, [
            'الهاتف',
            'الموبايل',
            'واتساب',
            'البريد الإلكتروني',
            'العنوان بالعربي',
            'العنوان بالانكليزي',
            'الخريطة',
            'ساعات العمل بالعربي',
            'ساعات العمل بالانكليزي',
            'تاريخ التحديث',
        ], $rows);
    }

    private function buildInternalHeadersSheet(Worksheet $sheet): void
    {
        $sheet->setTitle('هيدرات الصفحات الداخلية');

        $rows = InternalPageHeader::query()
            ->latest('id')
            ->get()
            ->map(fn (InternalPageHeader $item): array => [
                $this->value($item, ['section_key', 'page_key']),
                $this->value($item, ['title_ar']),
                $this->value($item, ['title_en']),
                $this->value($item, ['image']),
                $this->statusText($this->value($item, ['status', 'is_active'])),
                $this->dateTime($this->value($item, ['updated_at'])),
            ])
            ->all();

        $this->fillSheet($sheet, [
            'مفتاح الصفحة',
            'العنوان بالعربي',
            'العنوان بالانكليزي',
            'الصورة',
            'الحالة',
            'تاريخ التحديث',
        ], $rows);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<int, mixed>>  $rows
     */
    private function fillSheet(Worksheet $sheet, array $headers, array $rows): void
    {
        $sheet->fromArray($headers, null, 'A1');

        if ($rows !== []) {
            $sheet->fromArray($rows, null, 'A2');
        }

        $highestColumn = $sheet->getHighestColumn();
        $sheet->getStyle('A1:' . $highestColumn . '1')->getFont()->setBold(true);
        $sheet->freezePane('A2');

        $lastColumnIndex = Coordinate::columnIndexFromString($highestColumn);
        for ($column = 1; $column <= $lastColumnIndex; $column++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setAutoSize(true);
        }
    }

    private function boolText(mixed $value): string
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ? 'نعم' : 'لا';
    }

    private function statusText(mixed $value): string
    {
        return match ((string) $value) {
            'active' => 'فعال',
            'inactive' => 'غير فعال',
            'published' => 'منشور',
            'draft' => 'مسودة',
            'archived' => 'مؤرشف',
            default => (string) $value,
        };
    }

    /**
     * @param  array<int, string>  $keys
     */
    private function value(Model $model, array $keys): string
    {
        foreach ($keys as $key) {
            if (! $model->offsetExists($key)) {
                continue;
            }

            $value = $model->getAttribute($key);

            if ($value === null) {
                continue;
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i');
            }

            if (is_array($value)) {
                return implode(', ', array_filter(array_map(static fn (mixed $item): string => (string) $item, $value)));
            }

            $string = trim((string) $value);
            if ($string !== '') {
                return $string;
            }
        }

        return '';
    }

    private function dateTime(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i');
        }

        return trim((string) $value);
    }
}
