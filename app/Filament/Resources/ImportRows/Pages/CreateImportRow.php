<?php

namespace App\Filament\Resources\ImportRows\Pages;

use App\Filament\Resources\ImportRows\ImportRowResource;
use Filament\Resources\Pages\CreateRecord;

class CreateImportRow extends CreateRecord
{
    protected static string $resource = ImportRowResource::class;
    protected static ?string $title = 'إضافة سطر استيراد';
    protected static ?string $breadcrumb = 'أسطر الاستيراد';
}
