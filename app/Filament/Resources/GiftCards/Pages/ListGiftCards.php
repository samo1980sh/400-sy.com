<?php

namespace App\Filament\Resources\GiftCards\Pages;

use App\Filament\Resources\GiftCards\GiftCardResource;
use Filament\Resources\Pages\ListRecords;

class ListGiftCards extends ListRecords
{
    protected static string $resource = GiftCardResource::class;
    protected static ?string $title = 'طلبات بطاقات الهدايا';
    protected static ?string $breadcrumb = 'طلبات بطاقات الهدايا';
}
