<?php

namespace App\Filament\Resources\Sizes\Pages;

use App\Filament\Resources\Sizes\SizeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSize extends EditRecord
{
    protected static string $resource = SizeResource::class;
    protected static ?string $title = 'تعديل قياس';
    protected static ?string $breadcrumb = 'القياسات';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
