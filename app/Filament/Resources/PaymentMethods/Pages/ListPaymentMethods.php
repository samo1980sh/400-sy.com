<?php

namespace App\Filament\Resources\PaymentMethods\Pages;

use App\Filament\Resources\PaymentMethods\PaymentMethodResource;
use App\Services\SalesSettingsExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListPaymentMethods extends ListRecords
{
    protected static string $resource = PaymentMethodResource::class;
    protected static ?string $title = 'طرق الدفع';
    protected static ?string $breadcrumb = 'طرق الدفع';
    protected function getHeaderActions(): array
    {
        return [
            $this->exportAction(),
        ];
    }

    protected function exportAction(): Action
    {
        return Action::make('exportSalesSettings')
            ->label('تصدير')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('success')
            ->action(fn () => app(SalesSettingsExportService::class)->download());
    }
}
