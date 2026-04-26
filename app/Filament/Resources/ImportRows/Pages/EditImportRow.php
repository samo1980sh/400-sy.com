<?php

namespace App\Filament\Resources\ImportRows\Pages;

use App\Filament\Resources\ImportRows\ImportRowResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditImportRow extends EditRecord
{
    protected static string $resource = ImportRowResource::class;
    protected static ?string $title = 'تعديل سطر استيراد';
    protected static ?string $breadcrumb = 'أسطر الاستيراد';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
