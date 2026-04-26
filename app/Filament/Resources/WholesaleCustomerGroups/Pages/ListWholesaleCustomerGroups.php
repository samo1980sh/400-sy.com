<?php

namespace App\Filament\Resources\WholesaleCustomerGroups\Pages;

use App\Filament\Resources\WholesaleCustomerGroups\WholesaleCustomerGroupResource;
use Filament\Resources\Pages\ListRecords;

class ListWholesaleCustomerGroups extends ListRecords
{
    protected static string $resource = WholesaleCustomerGroupResource::class;
    protected static ?string $title = 'فئات التاجر';
    protected static ?string $breadcrumb = 'فئات التاجر';
}
