<?php

namespace App\Filament\Resources\ShippingMethods\Pages;

use App\Filament\Resources\ShippingMethods\ShippingMethodResource;
use App\Services\SalesSettingsExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListShippingMethods extends ListRecords
{
    protected static string $resource = ShippingMethodResource::class;
    protected static ?string $title = 'طرق الشحن';
    protected static ?string $breadcrumb = 'طرق الشحن';
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
