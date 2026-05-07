<?php

namespace App\Filament\Resources\CompanyNewsTickerItems\Pages;

use App\Filament\Resources\CompanyNewsTickerItems\CompanyNewsTickerItemResource;
use App\Services\CompanyContentExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCompanyNewsTickerItems extends ListRecords
{
    protected static string $resource = CompanyNewsTickerItemResource::class;

    protected static ?string $title = 'الشريط الإخباري المتحرك';

    protected static ?string $breadcrumb = 'الشريط الإخباري المتحرك';

    protected function getHeaderActions(): array
    {
        return [
            $this->exportAction(),
        ];
    }

    protected function exportAction(): Action
    {
        return Action::make('exportCompanyContent')
            ->label('تصدير')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('success')
            ->action(fn () => app(CompanyContentExportService::class)->download());
    }
}
