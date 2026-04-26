<?php

namespace App\Filament\Resources\ImportBatches\Pages;

use App\Filament\Resources\ImportBatches\ImportBatchResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImportBatches extends ListRecords
{
    protected static string $resource = ImportBatchResource::class;
    protected static ?string $title = 'سجل الاستيراد';
    protected static ?string $breadcrumb = 'سجل الاستيراد';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
