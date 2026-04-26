<?php

namespace App\Filament\Resources\PointVoucherRedemptions\Pages;

use App\Filament\Resources\PointVoucherRedemptions\PointVoucherRedemptionResource;
use Filament\Resources\Pages\ListRecords;

class ListPointVoucherRedemptions extends ListRecords
{
    protected static string $resource = PointVoucherRedemptionResource::class;
    protected static ?string $title = 'سجل قسائم النقاط';
    protected static ?string $breadcrumb = 'سجل قسائم النقاط';
}
