<?php

namespace App\Filament\Resources\RetailCustomerGroups\Pages;

use App\Filament\Resources\RetailCustomerGroups\RetailCustomerGroupResource;
use Filament\Resources\Pages\ListRecords;

class ListRetailCustomerGroups extends ListRecords
{
    protected static string $resource = RetailCustomerGroupResource::class;
    protected static ?string $title = 'فئات المفرق';
    protected static ?string $breadcrumb = 'فئات المفرق';
}
