<?php

namespace App\Filament\Resources\GiftCards\Pages;

use App\Filament\Resources\GiftCards\GiftCardResource;
use App\Services\SalesSettingsExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListGiftCards extends ListRecords
{
    protected static string $resource = GiftCardResource::class;
    protected static ?string $title = 'طلبات بطاقات الهدايا';
    protected static ?string $breadcrumb = 'طلبات بطاقات الهدايا';
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
