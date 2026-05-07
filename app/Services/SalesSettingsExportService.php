<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\GiftCard;
use App\Models\GiftCardRedemption;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesSettingsExportService
{
    public function download(): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet();
        $fileName = 'sales-settings-export-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function spreadsheet(): Spreadsheet
    {
        $coupons = Coupon::query()
            ->with(['creator'])
            ->withCount('redemptions')
            ->latest('id')
            ->get();

        $couponRedemptions = CouponRedemption::query()
            ->with(['coupon', 'order.customer'])
            ->latest('id')
            ->get();

        $giftCards = GiftCard::query()
            ->with(['customer'])
            ->latest('id')
            ->get();

        $giftCardRedemptions = GiftCardRedemption::query()
            ->with(['giftCard.customer', 'order'])
            ->latest('id')
            ->get();

        $paymentMethods = PaymentMethod::query()
            ->orderBy('id', 'desc')
            ->get();

        $shippingMethods = ShippingMethod::query()
            ->orderBy('id', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet();

        $couponsSheet = $spreadsheet->getActiveSheet();
        $couponsSheet->setTitle('الكوبونات');
        $this->buildCouponsSheet($couponsSheet, $coupons);

        $couponRedemptionsSheet = $spreadsheet->createSheet();
        $couponRedemptionsSheet->setTitle('استخدامات الكوبونات');
        $this->buildCouponRedemptionsSheet($couponRedemptionsSheet, $couponRedemptions);

        $giftCardsSheet = $spreadsheet->createSheet();
        $giftCardsSheet->setTitle('بطاقات الهدايا');
        $this->buildGiftCardsSheet($giftCardsSheet, $giftCards);

        $giftCardRedemptionsSheet = $spreadsheet->createSheet();
        $giftCardRedemptionsSheet->setTitle('استخدامات بطاقات الهدايا');
        $this->buildGiftCardRedemptionsSheet($giftCardRedemptionsSheet, $giftCardRedemptions);

        $paymentMethodsSheet = $spreadsheet->createSheet();
        $paymentMethodsSheet->setTitle('طرق الدفع');
        $this->buildPaymentMethodsSheet($paymentMethodsSheet, $paymentMethods);

        $shippingMethodsSheet = $spreadsheet->createSheet();
        $shippingMethodsSheet->setTitle('طرق الشحن');
        $this->buildShippingMethodsSheet($shippingMethodsSheet, $shippingMethods);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    protected function buildCouponsSheet(Worksheet $sheet, $coupons): void
    {
        $rows = [[
            'كود الكوبون',
            'النوع',
            'القيمة',
            'الحد الأدنى للطلب',
            'الحد الأقصى للحسم',
            'عدد مرات الاستخدام المسموح',
            'عدد مرات الاستخدام الحالية',
            'يبدأ في',
            'ينتهي في',
            'الحالة',
            'أنشئ بواسطة',
            'تاريخ الإنشاء',
        ]];

        foreach ($coupons as $coupon) {
            $rows[] = [
                $coupon->code ?: '',
                $coupon->discount_type ?: '',
                $coupon->discount_value ?? '',
                '',
                '',
                $coupon->usage_limit_per_customer ?? '',
                $coupon->redemptions_count ?? 0,
                $coupon->starts_at?->format('Y-m-d H:i') ?: '',
                $coupon->ends_at?->format('Y-m-d H:i') ?: '',
                $this->formatActiveStatus($coupon->status),
                $coupon->creator?->name ?: '',
                $coupon->created_at?->format('Y-m-d H:i') ?: '',
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function buildCouponRedemptionsSheet(Worksheet $sheet, $redemptions): void
    {
        $rows = [[
            'كود الكوبون',
            'رقم الطلب',
            'اسم الزبون',
            'قيمة الحسم',
            'تاريخ الاستخدام',
        ]];

        foreach ($redemptions as $redemption) {
            $rows[] = [
                $redemption->coupon?->code ?: '',
                $redemption->order?->order_no ?: $redemption->order_no ?: '',
                $redemption->order?->customer?->name ?: $redemption->customer_name ?: '',
                $redemption->discount_amount ?? '',
                $redemption->applied_at?->format('Y-m-d H:i') ?: $redemption->created_at?->format('Y-m-d H:i') ?: '',
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function buildGiftCardsSheet(Worksheet $sheet, $giftCards): void
    {
        $rows = [[
            'كود البطاقة',
            'اسم الزبون',
            'القيمة',
            'الرصيد',
            'تاريخ الإصدار',
            'تاريخ الانتهاء',
            'الحالة',
            'تاريخ الإنشاء',
        ]];

        foreach ($giftCards as $giftCard) {
            $rows[] = [
                $giftCard->code ?: '',
                $giftCard->customer?->name ?: '',
                $giftCard->amount ?? '',
                $giftCard->balance ?? '',
                $giftCard->issued_at?->format('Y-m-d H:i') ?: '',
                $giftCard->expires_at?->format('Y-m-d H:i') ?: '',
                $this->formatGiftCardStatus($giftCard->status),
                $giftCard->created_at?->format('Y-m-d H:i') ?: '',
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function buildGiftCardRedemptionsSheet(Worksheet $sheet, $redemptions): void
    {
        $rows = [[
            'كود البطاقة',
            'رقم الطلب',
            'اسم الزبون',
            'القيمة المستخدمة',
            'تاريخ الاستخدام',
        ]];

        foreach ($redemptions as $redemption) {
            $rows[] = [
                $redemption->giftCard?->code ?: '',
                $redemption->order?->order_no ?: $redemption->order_no ?: '',
                $redemption->giftCard?->customer?->name ?: $redemption->customer_name ?: '',
                $redemption->amount_used ?? '',
                $redemption->applied_at?->format('Y-m-d H:i') ?: $redemption->created_at?->format('Y-m-d H:i') ?: '',
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function buildPaymentMethodsSheet(Worksheet $sheet, $paymentMethods): void
    {
        $rows = [[
            'الاسم بالعربي',
            'الاسم بالانكليزي',
            'الكود',
            'الوصف',
            'الترتيب',
            'فعال',
            'تاريخ الإنشاء',
        ]];

        foreach ($paymentMethods as $paymentMethod) {
            $rows[] = [
                $paymentMethod->name_ar ?: '',
                $paymentMethod->name_en ?: '',
                $paymentMethod->code ?: '',
                $paymentMethod->notes ?: '',
                '',
                $paymentMethod->active ? 'نعم' : 'لا',
                $paymentMethod->created_at?->format('Y-m-d H:i') ?: '',
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function buildShippingMethodsSheet(Worksheet $sheet, $shippingMethods): void
    {
        $rows = [[
            'الاسم بالعربي',
            'الاسم بالانكليزي',
            'الكود',
            'التكلفة',
            'الوصف',
            'الترتيب',
            'فعال',
            'تاريخ الإنشاء',
        ]];

        foreach ($shippingMethods as $shippingMethod) {
            $rows[] = [
                $shippingMethod->name_ar ?: '',
                $shippingMethod->name_en ?: '',
                $shippingMethod->code ?: '',
                $shippingMethod->cost ?? '',
                $shippingMethod->notes ?: '',
                '',
                $shippingMethod->active ? 'نعم' : 'لا',
                $shippingMethod->created_at?->format('Y-m-d H:i') ?: '',
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function formatActiveStatus(?string $status): string
    {
        return match ($status) {
            'active' => 'فعال',
            'inactive' => 'غير فعال',
            null, '' => '',
            default => (string) $status,
        };
    }

    protected function formatGiftCardStatus(?string $status): string
    {
        return match ($status) {
            'active' => 'فعالة',
            'inactive' => 'غير فعالة',
            null, '' => '',
            default => (string) $status,
        };
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
