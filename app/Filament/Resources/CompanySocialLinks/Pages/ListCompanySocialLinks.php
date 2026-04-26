<?php

namespace App\Filament\Resources\CompanySocialLinks\Pages;

use App\Filament\Resources\CompanySocialLinks\CompanySocialLinkResource;
use Filament\Resources\Pages\ListRecords;

class ListCompanySocialLinks extends ListRecords
{
    protected static string $resource = CompanySocialLinkResource::class;
    protected static ?string $title = 'روابط التواصل الاجتماعي';
    protected static ?string $breadcrumb = 'روابط التواصل الاجتماعي';
}
