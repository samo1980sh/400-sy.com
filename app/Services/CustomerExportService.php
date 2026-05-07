<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\CustomerLoyaltyTransaction;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerExportService
{
    public function download(): StreamedResponse
    {
        $spreadsheet = $this->spreadsheet();
        $fileName = 'customers-export-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function spreadsheet(): Spreadsheet
    {
        $customers = Customer::query()
            ->with([
                'retailGroups',
                'addresses',
                'orders',
                'loyaltyWallet',
            ])
            ->latest('id')
            ->get();

        $addresses = CustomerAddress::query()
            ->with('customer')
            ->latest('id')
            ->get();

        $transactions = CustomerLoyaltyTransaction::query()
            ->with('customer')
            ->latest('id')
            ->get();

        $spreadsheet = new Spreadsheet();

        $customersSheet = $spreadsheet->getActiveSheet();
        $customersSheet->setTitle('العملاء');
        $this->buildCustomersSheet($customersSheet, $customers);

        $addressesSheet = $spreadsheet->createSheet();
        $addressesSheet->setTitle('عناوين العملاء');
        $this->buildAddressesSheet($addressesSheet, $addresses);

        $transactionsSheet = $spreadsheet->createSheet();
        $transactionsSheet->setTitle('معاملات الولاء');
        $this->buildTransactionsSheet($transactionsSheet, $transactions);

        $groupsSheet = $spreadsheet->createSheet();
        $groupsSheet->setTitle('فئات العملاء');
        $this->buildGroupsSheet($groupsSheet, $customers);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    protected function buildCustomersSheet(Worksheet $sheet, $customers): void
    {
        $rows = [[
            'رقم الحساب',
            'الاسم الكامل',
            'الموبايل',
            'الموبايل الثانوي',
            'البريد الإلكتروني',
            'الجنس',
            'تاريخ الميلاد',
            'الجنسية',
            'المدينة',
            'المنطقة',
            'المهنة',
            'الحالة الاجتماعية',
            'فئات المفرق',
            'عدد العناوين',
            'عدد الطلبات',
            'نقاط الولاء الحالية',
            'مجموع النقاط المكتسبة',
            'مجموع النقاط المصروفة',
            'الحالة',
            'ملاحظات',
            'تاريخ الإنشاء',
        ]];

        foreach ($customers as $customer) {
            $rows[] = [
                $customer->account_no ?: '',
                $customer->name ?: '',
                $customer->mobile ?: '',
                $customer->secondary_mobile ?: '',
                $customer->email ?: '',
                $this->formatGender($customer->gender),
                $customer->birth_date?->format('Y-m-d') ?: '',
                $customer->nationality ?: '',
                $customer->city ?: '',
                $customer->area ?: '',
                $customer->job_title ?: '',
                $customer->marital_status ?: '',
                $customer->retailGroups->pluck('name')->filter()->implode('، '),
                $customer->addresses->count(),
                $customer->orders->count(),
                $customer->loyaltyWallet?->points_balance ?? '',
                $customer->loyaltyWallet?->points_earned_total ?? '',
                $customer->loyaltyWallet?->points_spent_total ?? '',
                $this->formatCustomerStatus($customer->status),
                $customer->notes ?: '',
                $customer->created_at?->format('Y-m-d H:i') ?: '',
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function buildAddressesSheet(Worksheet $sheet, $addresses): void
    {
        $rows = [[
            'رقم حساب العميل',
            'اسم العميل',
            'اسم العنوان',
            'الموبايل',
            'المدينة',
            'المنطقة',
            'العنوان',
            'ملاحظات',
            'افتراضي',
            'فعال',
            'تاريخ الإنشاء',
        ]];

        foreach ($addresses as $address) {
            $rows[] = [
                $address->customer?->account_no ?: '',
                $address->customer?->name ?: '',
                $address->label ?: '',
                $address->mobile ?: '',
                $address->city ?: '',
                $address->area ?: '',
                $address->address_line ?: '',
                $address->notes ?: '',
                $address->is_default ? 'نعم' : 'لا',
                '',
                $address->created_at?->format('Y-m-d H:i') ?: '',
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function buildTransactionsSheet(Worksheet $sheet, $transactions): void
    {
        $rows = [[
            'رقم حساب العميل',
            'اسم العميل',
            'النوع',
            'النقاط',
            'الرصيد قبل',
            'الرصيد بعد',
            'المرجع',
            'الملاحظة',
            'التاريخ',
        ]];

        foreach ($transactions as $transaction) {
            $reference = trim(implode(' #', array_filter([
                $transaction->source_type,
                $transaction->source_id,
            ])));

            if ($reference === '' && filled($transaction->reference_no)) {
                $reference = (string) $transaction->reference_no;
            }

            $rows[] = [
                $transaction->customer?->account_no ?: '',
                $transaction->customer?->name ?: '',
                $transaction->type ?: '',
                $transaction->points ?? '',
                $transaction->balance_before ?? '',
                $transaction->balance_after ?? '',
                $reference,
                $transaction->notes ?: '',
                $transaction->occurred_at?->format('Y-m-d H:i') ?: $transaction->created_at?->format('Y-m-d H:i') ?: '',
            ];
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function buildGroupsSheet(Worksheet $sheet, $customers): void
    {
        $rows = [[
            'رقم حساب العميل',
            'اسم العميل',
            'فئة المفرق',
        ]];

        foreach ($customers as $customer) {
            foreach ($customer->retailGroups as $group) {
                $rows[] = [
                    $customer->account_no ?: '',
                    $customer->name ?: '',
                    $group->name ?: '',
                ];
            }
        }

        $this->fillSheet($sheet, $rows);
    }

    protected function formatGender(?string $gender): string
    {
        return match ($gender) {
            'male' => 'ذكر',
            'female' => 'أنثى',
            null, '' => '',
            default => (string) $gender,
        };
    }

    protected function formatCustomerStatus(?string $status): string
    {
        return match ($status) {
            'active' => 'فعال',
            'inactive' => 'غير فعال',
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
