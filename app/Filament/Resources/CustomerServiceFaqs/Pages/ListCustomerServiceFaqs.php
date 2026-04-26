<?php

namespace App\Filament\Resources\CustomerServiceFaqs\Pages;

use App\Filament\Resources\CustomerServiceFaqs\CustomerServiceFaqResource;
use Filament\Resources\Pages\ListRecords;

class ListCustomerServiceFaqs extends ListRecords
{
    protected static string $resource = CustomerServiceFaqResource::class;
    protected static ?string $title = 'الأسئلة الشائعة';
    protected static ?string $breadcrumb = 'الأسئلة الشائعة';
}
