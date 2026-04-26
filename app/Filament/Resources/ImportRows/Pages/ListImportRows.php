<?php

namespace App\Filament\Resources\ImportRows\Pages;

use App\Filament\Resources\ImportRows\ImportRowResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImportRows extends ListRecords
{
    protected static string $resource = ImportRowResource::class;
    protected static ?string $title = 'أسطر الاستيراد';
    protected static ?string $breadcrumb = 'أسطر الاستيراد';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
