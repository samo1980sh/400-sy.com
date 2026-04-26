<?php

namespace App\Filament\Resources\Sizes\Pages;

use App\Filament\Resources\Sizes\SizeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSize extends CreateRecord
{
    protected static string $resource = SizeResource::class;
    protected static ?string $title = 'إضافة قياس';
    protected static ?string $breadcrumb = 'القياسات';
}
