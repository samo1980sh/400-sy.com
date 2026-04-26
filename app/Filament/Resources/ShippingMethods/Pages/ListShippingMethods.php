<?php

namespace App\Filament\Resources\ShippingMethods\Pages;

use App\Filament\Resources\ShippingMethods\ShippingMethodResource;
use Filament\Resources\Pages\ListRecords;

class ListShippingMethods extends ListRecords
{
    protected static string $resource = ShippingMethodResource::class;
    protected static ?string $title = 'طرق الشحن';
    protected static ?string $breadcrumb = 'طرق الشحن';
}
