<?php

namespace App\Filament\Resources\Colors\Pages;

use App\Filament\Resources\Colors\ColorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateColor extends CreateRecord
{
    protected static string $resource = ColorResource::class;
    protected static ?string $title = 'إضافة لون';
    protected static ?string $breadcrumb = 'الألوان';
}
