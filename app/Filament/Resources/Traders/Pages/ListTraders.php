<?php

namespace App\Filament\Resources\Traders\Pages;

use App\Filament\Resources\Traders\TraderResource;
use App\Services\TraderExportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListTraders extends ListRecords
{
    protected static string $resource = TraderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->exportAction(),
            CreateAction::make(),
        ];
    }

    protected function exportAction(): Action
    {
        return Action::make('exportTraders')
            ->label('تصدير')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('success')
            ->action(fn () => app(TraderExportService::class)->download());
    }
}
