<?php

namespace App\Filament\Resources\CustomerServiceFaqs\Pages;

use App\Filament\Resources\CustomerServiceFaqs\CustomerServiceFaqResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;

class CreateCustomerServiceFaq extends CreateRecord
{
    protected static string $resource = CustomerServiceFaqResource::class;
    protected static ?string $title = 'إضافة سؤال شائع';
    protected static ?string $breadcrumb = 'الأسئلة الشائعة';

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::SevenExtraLarge;
    }
}
