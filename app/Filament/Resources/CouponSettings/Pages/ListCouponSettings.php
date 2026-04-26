<?php

namespace App\Filament\Resources\CouponSettings\Pages;

use App\Filament\Resources\CouponSettings\CouponSettingResource;
use App\Models\CouponSetting;
use Filament\Resources\Pages\ListRecords;

class ListCouponSettings extends ListRecords
{
    protected static string $resource = CouponSettingResource::class;
    protected static ?string $title = 'إعدادات الكوبونات';
    protected static ?string $breadcrumb = 'إعدادات الكوبونات';

    public function mount(): void
    {
        CouponSetting::singleton();

        parent::mount();
    }
}
