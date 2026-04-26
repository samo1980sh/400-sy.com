<?php

namespace App\Filament\Resources\WarehouseInventoryItems\Pages;

use App\Filament\Resources\WarehouseInventoryItems\WarehouseInventoryItemResource;
use Filament\Resources\Pages\ListRecords;

class ListWarehouseInventoryItems extends ListRecords
{
    protected static string $resource = WarehouseInventoryItemResource::class;
}
