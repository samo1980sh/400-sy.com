<?php

namespace App\Filament\Resources\CustomerLoyaltyWallets\Pages;

use App\Filament\Resources\CustomerLoyaltyWallets\CustomerLoyaltyWalletResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerLoyaltyWallets extends ListRecords
{
    protected static string $resource = CustomerLoyaltyWalletResource::class;
    protected static ?string $title = 'الولاءات والنقاط';
    protected static ?string $breadcrumb = 'الولاءات والنقاط';
}
