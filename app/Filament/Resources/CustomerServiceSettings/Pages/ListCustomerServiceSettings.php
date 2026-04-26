<?php

namespace App\Filament\Resources\CustomerServiceSettings\Pages;

use App\Filament\Resources\CustomerServiceSettings\CustomerServiceSettingResource;
use App\Models\CustomerServiceSetting;
use Filament\Resources\Pages\ListRecords;

class ListCustomerServiceSettings extends ListRecords
{
    protected static string $resource = CustomerServiceSettingResource::class;
    protected static ?string $title = 'إعدادات خدمة الزبائن';
    protected static ?string $breadcrumb = 'إعدادات خدمة الزبائن';

    public function mount(): void
    {
        CustomerServiceSetting::seedDefaults();
        CustomerServiceSetting::syncDefaults();

        parent::mount();
    }
}
