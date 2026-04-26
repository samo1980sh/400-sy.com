<?php

namespace App\Filament\Resources\CouponRedemptions\Pages;

use App\Filament\Resources\CouponRedemptions\CouponRedemptionResource;
use Filament\Resources\Pages\ListRecords;

class ListCouponRedemptions extends ListRecords
{
    protected static string $resource = CouponRedemptionResource::class;
    protected static ?string $title = 'سجل الكوبونات';
    protected static ?string $breadcrumb = 'سجل الكوبونات';
}
