<?php

namespace App\Filament\Resources\CompanyPages\Pages;

use App\Filament\Resources\CompanyPages\CompanyPageResource;
use Filament\Resources\Pages\ListRecords;

class ListCompanyPages extends ListRecords
{
    protected static string $resource = CompanyPageResource::class;
    protected static ?string $title = 'حول الشركة';
    protected static ?string $breadcrumb = 'حول الشركة';
}
