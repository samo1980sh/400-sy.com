<?php

namespace App\Filament\Resources\CompanyHeaderImages\Pages;

use App\Filament\Resources\CompanyHeaderImages\CompanyHeaderImageResource;
use App\Services\CompanyContentExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCompanyHeaderImages extends ListRecords
{
    protected static string $resource = CompanyHeaderImageResource::class;

    protected static ?string $title = 'سلايدر الصفحة الرئيسية';

    protected static ?string $breadcrumb = 'سلايدر الصفحة الرئيسية';

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
