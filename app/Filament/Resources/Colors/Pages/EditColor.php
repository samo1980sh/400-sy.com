<?php

namespace App\Filament\Resources\Colors\Pages;

use App\Filament\Resources\Colors\ColorResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditColor extends EditRecord
{
    protected static string $resource = ColorResource::class;
    protected static ?string $title = 'تعديل لون';
    protected static ?string $breadcrumb = 'الألوان';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
