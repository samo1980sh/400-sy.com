<?php

namespace App\Filament\Resources\CustomerQrLogs\Pages;

use App\Filament\Resources\CustomerQrLogs\CustomerQrLogResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerQrLogs extends ListRecords
{
    protected static string $resource = CustomerQrLogResource::class;
    protected static ?string $title = 'سجل استخدام QR';
    protected static ?string $breadcrumb = 'سجل استخدام QR';
}
