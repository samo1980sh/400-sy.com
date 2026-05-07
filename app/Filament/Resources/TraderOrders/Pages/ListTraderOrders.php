<?php

namespace App\Filament\Resources\TraderOrders\Pages;

use App\Filament\Resources\TraderOrders\TraderOrderResource;
use App\Services\TraderExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListTraderOrders extends ListRecords
{
    protected static string $resource = TraderOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->exportAction(),
        ];
    }

    protected function exportAction(): Action
    {
        return Action::make('exportTraderOrders')
            ->label('تصدير')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('success')
            ->action(fn () => app(TraderExportService::class)->download());
    }
}
