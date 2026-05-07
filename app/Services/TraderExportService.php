<?php

namespace App\Services;

use App\Models\Trader;
use App\Models\TraderOrder;
use App\Models\TraderOrderItem;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TraderExportService
{
    public function download(): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet();
        $fileName = 'traders-orders-export-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function spreadsheet(): Spreadsheet
    {
        $traders = Trader::query()
            ->with('wholesaleCustomerGroup')
            ->latest('id')
            ->get();

        $orders = TraderOrder::query()
            ->with(['trader.wholesaleCustomerGroup', 'items'])
            ->latest('id')
            ->get();

        $items = TraderOrderItem::query()
            ->with(['traderOrder.trader', 'product', 'wholesaleColor'])
            ->latest('id')
            ->get();

        $spreadsheet = new Spreadsheet();

        $tradersSheet = $spreadsheet->getActiveSheet();
        $tradersSheet->setTitle('التجار');
        $this->buildTradersSheet($tradersSheet, $traders);

        $ordersSheet = $spreadsheet->createSheet();
        $ordersSheet->setTitle('طلبات التجار');
        $this->buildOrdersSheet($ordersSheet, $orders);

        $itemsSheet = $spreadsheet->createSheet();
        $itemsSheet->setTitle('عناصر الطلبات');
        $this->buildItemsSheet($itemsSheet, $items);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    protected function buildTradersSheet(Worksheet $sheet, $traders): void
    {
        $rows = [[
            'اسم التاجر',
            'البريد الإلكتروني',
            'الهاتف',
            'اسم الشركة',
            'فئة التاجر',
            'المدينة',
            'العنوان',
            'الحالة',
            'تاريخ الإنشاء',
        ]];

        foreach ($traders as $trader) {
            $rows[] = [
                $trader->name ?: '',
                $trader->email ?: '',
                $trader->mobile ?: '',
                '',
                $trader->wholesaleCustomerGroup?->name_ar ?: '',
                $trader->city ?: '',
                $trader->address_line ?: '',
                $this->formatTraderStatus($trader->status),
                $trader->created_at?->format('Y-m-d H:i') ?: '',
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function buildOrdersSheet(Worksheet $sheet, $orders): void
    {
        $rows = [[
            'رقم الطلب',
            'اسم التاجر',
            'فئة التاجر',
            'حالة الطلب',
            'حالة الدفع',
            'عدد العناصر',
            'الإجمالي الفرعي',
            'قيمة الحسم',
            'تكلفة الشحن',
            'الإجمالي',
            'تاريخ التأكيد',
            'تاريخ الشحن',
            'تاريخ التسليم',
            'تاريخ الإلغاء',
            'تاريخ الإنشاء',
        ]];

        foreach ($orders as $order) {
            $rows[] = [
                $order->order_no ?: '',
                $order->trader?->name ?: '',
                $order->trader?->wholesaleCustomerGroup?->name_ar ?: '',
                $this->formatOrderStatus($order->status),
                $this->formatPaymentStatus($order->payment_status),
                $order->items->count(),
                $order->total_before_discount ?? '',
                $order->discount_value ?? '',
                $order->shipping_cost ?? '',
                $order->total ?? '',
                $order->confirmed_at?->format('Y-m-d H:i') ?: '',
                $order->shipped_at?->format('Y-m-d H:i') ?: '',
                $order->delivered_at?->format('Y-m-d H:i') ?: '',
                $order->cancelled_at?->format('Y-m-d H:i') ?: '',
                $order->created_at?->format('Y-m-d H:i') ?: '',
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function buildItemsSheet(Worksheet $sheet, $items): void
    {
        $rows = [[
            'رقم الطلب',
            'اسم التاجر',
            'رمز المنتج',
            'اسم المنتج',
            'رمز لون الجملة',
            'اسم لون الجملة',
            'نص السيرية / المقاس',
            'الكمية',
            'سعر الوحدة',
            'إجمالي السطر',
        ]];

        foreach ($items as $item) {
            $rows[] = [
                $item->traderOrder?->order_no ?: '',
                $item->traderOrder?->trader?->name ?: '',
                $item->product?->model_no ?: '',
                $item->product?->title_ar ?: '',
                $item->wholesaleColor?->color_code ?: '',
                $item->wholesaleColor?->color_name_ar ?: '',
                $item->size_text ?: '',
                $item->quantity ?? '',
                $item->unit_price ?? '',
                $item->line_total ?? '',
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function formatTraderStatus(?string $status): string
    {
        return match ($status) {
            'active' => 'فعال',
            'inactive' => 'غير فعال',
            'pending' => 'قيد المراجعة',
            'blocked' => 'محظور',
            null, '' => '',
            default => (string) $status,
        };
    }

    protected function formatOrderStatus(?string $status): string
    {
        return match ($status) {
            'pending' => 'قيد المراجعة',
            'confirmed' => 'مؤكد',
            'shipped' => 'مُشحن',
            'delivered' => 'مُسلم',
            'cancelled' => 'ملغى',
            null, '' => '',
            default => (string) $status,
        };
    }

    protected function formatPaymentStatus(?string $status): string
    {
        return match ($status) {
            'paid' => 'مدفوع',
            'unpaid' => 'غير مدفوع',
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
