<?php

namespace App\Filament\Resources\ContactInfoSettings\Pages;

use App\Filament\Resources\ContactInfoSettings\ContactInfoSettingResource;
use App\Models\ContactInfoSetting;
use Filament\Resources\Pages\ListRecords;

class ListContactInfoSettings extends ListRecords
{
    protected static string $resource = ContactInfoSettingResource::class;
    protected static ?string $title = 'معلومات اتصال عامة';
    protected static ?string $breadcrumb = 'معلومات اتصال عامة';

    public function mount(): void
    {
        ContactInfoSetting::singleton();

        parent::mount();
    }
}
