<?php

namespace App\Filament\Resources\ExchangeRateSettings\Pages;

use App\Filament\Resources\ExchangeRateSettings\ExchangeRateSettingResource;
use App\Models\ExchangeRateSetting;
use Filament\Resources\Pages\ListRecords;

class ListExchangeRateSettings extends ListRecords
{
    protected static string $resource = ExchangeRateSettingResource::class;
    protected static ?string $title = 'سعر الصرف';
    protected static ?string $breadcrumb = 'سعر الصرف';

    public function mount(): void
    {
        ExchangeRateSetting::singleton();

        parent::mount();
    }
}
