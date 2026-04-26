<?php

namespace App\Filament\Resources\CustomerLoyaltySettings\Pages;

use App\Filament\Resources\CustomerLoyaltySettings\CustomerLoyaltySettingResource;
use App\Models\CustomerLoyaltySetting;
use Filament\Resources\Pages\ListRecords;

class ListCustomerLoyaltySettings extends ListRecords
{
    protected static string $resource = CustomerLoyaltySettingResource::class;
    protected static ?string $title = 'إعدادات الولاء';
    protected static ?string $breadcrumb = 'إعدادات الولاء';

    public function mount(): void
    {
        CustomerLoyaltySetting::singleton();

        parent::mount();
    }
}
