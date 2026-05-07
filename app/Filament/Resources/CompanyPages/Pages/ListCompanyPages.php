<?php

namespace App\Filament\Resources\CompanyPages\Pages;

use App\Filament\Resources\CompanyPages\CompanyPageResource;
use App\Services\CompanyContentExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCompanyPages extends ListRecords
{
    protected static string $resource = CompanyPageResource::class;

    protected static ?string $title = 'حول الشركة';

    protected static ?string $breadcrumb = 'حول الشركة';

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
