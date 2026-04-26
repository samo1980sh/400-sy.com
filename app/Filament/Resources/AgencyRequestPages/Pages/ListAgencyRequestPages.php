<?php

namespace App\Filament\Resources\AgencyRequestPages\Pages;

use App\Filament\Resources\AgencyRequestPages\AgencyRequestPageResource;
use App\Models\AgencyRequestPage;
use Filament\Resources\Pages\ListRecords;

class ListAgencyRequestPages extends ListRecords
{
    protected static string $resource = AgencyRequestPageResource::class;
    protected static ?string $title = 'طلب وكالة';
    protected static ?string $breadcrumb = 'طلب وكالة';

    public function mount(): void
    {
        AgencyRequestPage::singleton();

        parent::mount();
    }
}
