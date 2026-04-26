<?php

namespace App\Filament\Resources\GiftCardRedemptions\Pages;

use App\Filament\Resources\GiftCardRedemptions\GiftCardRedemptionResource;
use Filament\Resources\Pages\ListRecords;

class ListGiftCardRedemptions extends ListRecords
{
    protected static string $resource = GiftCardRedemptionResource::class;
    protected static ?string $title = 'سجل بطاقات الهدايا';
    protected static ?string $breadcrumb = 'سجل بطاقات الهدايا';
}
