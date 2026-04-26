<?php

namespace App\Filament\Resources\CustomerLoyaltyTransactions\Pages;

use App\Filament\Resources\CustomerLoyaltyTransactions\CustomerLoyaltyTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerLoyaltyTransactions extends ListRecords
{
    protected static string $resource = CustomerLoyaltyTransactionResource::class;
    protected static ?string $title = 'سجل النقاط';
    protected static ?string $breadcrumb = 'سجل النقاط';
}
