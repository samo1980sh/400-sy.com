<?php

namespace App\Filament\Resources\CompanyNewsItems\Pages;

use App\Filament\Resources\CompanyNewsItems\CompanyNewsItemResource;
use App\Services\CompanyContentExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCompanyNewsItems extends ListRecords
{
    protected static string $resource = CompanyNewsItemResource::class;

    protected static ?string $title = 'الأخبار والأحداث';

    protected static ?string $breadcrumb = 'الأخبار والأحداث';

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
