<?php

namespace App\Filament\Resources\PointsVouchers\Pages;

use App\Filament\Resources\PointsVouchers\PointsVoucherResource;
use Filament\Resources\Pages\ListRecords;

class ListPointsVouchers extends ListRecords
{
    protected static string $resource = PointsVoucherResource::class;
    protected static ?string $title = 'قسائم النقاط';
    protected static ?string $breadcrumb = 'قسائم النقاط';
}
