<?php

namespace App\Filament\Resources\CompanyNewsTickerItems\Pages;

use App\Filament\Resources\CompanyNewsTickerItems\CompanyNewsTickerItemResource;
use Filament\Resources\Pages\ListRecords;

class ListCompanyNewsTickerItems extends ListRecords
{
    protected static string $resource = CompanyNewsTickerItemResource::class;
    protected static ?string $title = 'الشريط الإخباري المتحرك';
    protected static ?string $breadcrumb = 'الشريط الإخباري المتحرك';
}
