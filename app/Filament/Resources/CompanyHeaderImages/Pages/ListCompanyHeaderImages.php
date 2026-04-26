<?php

namespace App\Filament\Resources\CompanyHeaderImages\Pages;

use App\Filament\Resources\CompanyHeaderImages\CompanyHeaderImageResource;
use Filament\Resources\Pages\ListRecords;

class ListCompanyHeaderImages extends ListRecords
{
    protected static string $resource = CompanyHeaderImageResource::class;
    protected static ?string $title = 'سلايدر الصفحة الرئيسية';
    protected static ?string $breadcrumb = 'سلايدر الصفحة الرئيسية';
}
