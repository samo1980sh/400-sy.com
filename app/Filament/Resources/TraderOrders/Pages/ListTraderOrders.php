<?php

namespace App\Filament\Resources\TraderOrders\Pages;

use App\Filament\Resources\TraderOrders\TraderOrderResource;
use Filament\Resources\Pages\ListRecords;

class ListTraderOrders extends ListRecords
{
    protected static string $resource = TraderOrderResource::class;
}
