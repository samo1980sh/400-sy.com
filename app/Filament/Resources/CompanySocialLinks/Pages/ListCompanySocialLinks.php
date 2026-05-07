<?php

namespace App\Filament\Resources\CompanySocialLinks\Pages;

use App\Filament\Resources\CompanySocialLinks\CompanySocialLinkResource;
use App\Services\CompanyContentExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCompanySocialLinks extends ListRecords
{
    protected static string $resource = CompanySocialLinkResource::class;

    protected static ?string $title = 'روابط التواصل الاجتماعي';

    protected static ?string $breadcrumb = 'روابط التواصل الاجتماعي';

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
