<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Schema;

class OrderExportService
{
    public function download(): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet();
        $fileName = 'orders-export-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function spreadsheet(): Spreadsheet
    {
        $orders = Order::query()
            ->with([
                'customer',
                'shippingAddress',
                'shippingMethod',
                'couponRedemption.coupon',
                'items',
            ])
            ->latest('id')
            ->get();

        $items = OrderItem::query()
            ->with([
                'order.customer',
                'product',
                'productVariant.product',
                'productVariant.productColor',
                'productVariant.size',
            ])
            ->latest('id')
            ->get();

        $history = Schema::hasTable('order_status_history')
            ? OrderStatusHistory::query()
                ->with(['order', 'changedBy'])
                ->latest('id')
                ->get()
            : collect();

        $spreadsheet = new Spreadsheet();

        $ordersSheet = $spreadsheet->getActiveSheet();
        $ordersSheet->setTitle('الطلبات');
        $this->buildOrdersSheet($ordersSheet, $orders);

        $itemsSheet = $spreadsheet->createSheet();
        $itemsSheet->setTitle('عناصر الطلبات');
        $this->buildItemsSheet($itemsSheet, $items);

        $historySheet = $spreadsheet->createSheet();
        $historySheet->setTitle('سجل الحالات');
        $this->buildHistorySheet($historySheet, $history);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    protected function buildOrdersSheet(Worksheet $sheet, $orders): void
    {
        $rows = [[
            'رقم الطلب',
            'اسم الزبون',
            'هاتف الزبون',
            'رقم حساب الزبون',
            'عنوان الشحن',
            'المدينة',
            'المنطقة',
            'طريقة الشحن',
            'حالة الطلب',
            'حالة الدفع',
            'طريقة الدفع',
            'الكوبون',
            'الإجمالي قبل الحسم',
            'قيمة الحسم',
            'حسم الكوبون',
            'تكلفة الشحن',
            'الإجمالي',
            'هدية',
            'رسالة الهدية',
            'ملاحظات',
            'تاريخ التأكيد',
            'تاريخ الدفع',
            'تاريخ الشحن',
            'تاريخ التسليم',
            'تاريخ الإلغاء',
            'تاريخ الطلب',
        ]];

        foreach ($orders as $order) {
            $rows[] = [
                $order->order_no ?: '',
                $order->customer?->name ?: '',
                $order->customer?->mobile ?: '',
                $order->customer?->account_no ?: '',
                $order->shippingAddress?->label ?: '',
                $order->shippingAddress?->city ?: '',
                $order->shippingAddress?->area ?: '',
                $order->shippingMethod?->name_ar ?: '',
                $this->formatOrderStatus($order->status),
                $this->formatPaymentStatus($order->payment_status),
                $order->payment_method ?: '',
                $order->coupon_code_snapshot ?: '',
                $order->total_before_discount ?? '',
                $order->discount_value ?? '',
                $order->coupon_discount_value ?? '',
                $order->shipping_cost ?? '',
                $order->total ?? '',
                $order->is_gift ? 'نعم' : 'لا',
                $order->gift_message ?: '',
                $order->notes ?: '',
                $order->confirmed_at?->format('Y-m-d H:i') ?: '',
                $order->paid_at?->format('Y-m-d H:i') ?: '',
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
            'اسم الزبون',
            'رمز المنتج',
            'اسم المنتج',
            'SKU',
            'Barcode',
            'رمز اللون',
            'اسم اللون',
            'رمز القياس',
            'اسم القياس',
            'الكمية',
            'سعر الوحدة',
            'إجمالي السطر',
        ]];

        foreach ($items as $item) {
            $product = $item->product ?: $item->productVariant?->product;

            $rows[] = [
                $item->order?->order_no ?: '',
                $item->order?->customer?->name ?: '',
                $product?->model_no ?: '',
                $product?->title_ar ?: '',
                $item->productVariant?->sku ?: '',
                $item->productVariant?->barcode ?: '',
                $item->productVariant?->productColor?->color_code ?: '',
                $item->productVariant?->productColor?->color_name_ar ?: '',
                $item->productVariant?->size?->code ?: '',
                $item->productVariant?->size?->name_ar ?: '',
                $item->quantity ?? '',
                $item->unit_price ?? '',
                $item->line_total ?? '',
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function buildHistorySheet(Worksheet $sheet, $history): void
    {
        $rows = [[
            'رقم الطلب',
            'من حالة الطلب',
            'إلى حالة الطلب',
            'من حالة الدفع',
            'إلى حالة الدفع',
            'الملاحظة',
            'المستخدم',
            'التاريخ',
        ]];

        foreach ($history as $entry) {
            $rows[] = [
                $entry->order?->order_no ?: '',
                $this->formatOrderStatus($entry->from_status),
                $this->formatOrderStatus($entry->to_status),
                $this->formatPaymentStatus($entry->from_payment_status),
                $this->formatPaymentStatus($entry->to_payment_status),
                $entry->note ?: '',
                $entry->changedBy?->name ?: '',
                $entry->created_at?->format('Y-m-d H:i') ?: '',
            ];
        }

        $this->fillSheet($sheet, $rows);
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
