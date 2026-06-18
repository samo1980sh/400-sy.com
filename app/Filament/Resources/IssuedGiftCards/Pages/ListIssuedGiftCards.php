<?php

namespace App\Filament\Resources\IssuedGiftCards\Pages;

use App\Filament\Resources\IssuedGiftCards\IssuedGiftCardResource;
use Filament\Resources\Pages\ListRecords;

class ListIssuedGiftCards extends ListRecords
{
    protected static string $resource = IssuedGiftCardResource::class;
    protected static ?string $title = 'بطاقات الهدايا الصادرة';
    protected static ?string $breadcrumb = 'بطاقات الهدايا الصادرة';

    protected function getHeaderActions(): array
    {
        return [];
    }
}
