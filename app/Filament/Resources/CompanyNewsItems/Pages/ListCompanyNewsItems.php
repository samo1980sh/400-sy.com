<?php

namespace App\Filament\Resources\CompanyNewsItems\Pages;

use App\Filament\Resources\CompanyNewsItems\CompanyNewsItemResource;
use Filament\Resources\Pages\ListRecords;

class ListCompanyNewsItems extends ListRecords
{
    protected static string $resource = CompanyNewsItemResource::class;
    protected static ?string $title = 'الأخبار والأحداث';
    protected static ?string $breadcrumb = 'الأخبار والأحداث';
}
