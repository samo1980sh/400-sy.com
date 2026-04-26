<?php

namespace App\Filament\Resources\InternalPageHeaders\Pages;

use App\Filament\Resources\InternalPageHeaders\InternalPageHeaderResource;
use App\Models\InternalPageHeader;
use Filament\Resources\Pages\ListRecords;

class ListInternalPageHeaders extends ListRecords
{
    protected static string $resource = InternalPageHeaderResource::class;
    protected static ?string $title = 'هيدرات الصفحات الداخلية';
    protected static ?string $breadcrumb = 'هيدرات الصفحات الداخلية';

    public function mount(): void
    {
        InternalPageHeader::syncConfiguredSections();

        parent::mount();
    }
}
