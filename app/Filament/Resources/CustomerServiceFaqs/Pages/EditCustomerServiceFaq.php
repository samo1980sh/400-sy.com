<?php

namespace App\Filament\Resources\CustomerServiceFaqs\Pages;

use App\Filament\Resources\CustomerServiceFaqs\CustomerServiceFaqResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditCustomerServiceFaq extends EditRecord
{
    protected static string $resource = CustomerServiceFaqResource::class;
    protected static ?string $title = 'تعديل سؤال شائع';
    protected static ?string $breadcrumb = 'الأسئلة الشائعة';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    public function getMaxContentWidth(): Width | string | null
    {
        return Width::SevenExtraLarge;
    }
}
