<?php

namespace App\Filament\Resources\CustomerQrCodes\Pages;

use App\Filament\Resources\CustomerQrCodes\CustomerQrCodeResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerQrCodes extends ListRecords
{
    protected static string $resource = CustomerQrCodeResource::class;
    protected static ?string $title = 'QR Code';
    protected static ?string $breadcrumb = 'QR Code';
}
