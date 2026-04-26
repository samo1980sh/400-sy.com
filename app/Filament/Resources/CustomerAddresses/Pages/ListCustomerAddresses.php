<?php

namespace App\Filament\Resources\CustomerAddresses\Pages;

use App\Filament\Resources\CustomerAddresses\CustomerAddressResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerAddresses extends ListRecords
{
    protected static string $resource = CustomerAddressResource::class;
    protected static ?string $title = 'عناوين الزبائن';
    protected static ?string $breadcrumb = 'عناوين الزبائن';
}
