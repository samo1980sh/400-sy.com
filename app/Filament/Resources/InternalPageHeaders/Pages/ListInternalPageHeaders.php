<?php

namespace App\Filament\Resources\InternalPageHeaders\Pages;

use App\Filament\Resources\InternalPageHeaders\InternalPageHeaderResource;
use App\Models\InternalPageHeader;
use App\Services\CompanyContentExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListInternalPageHeaders extends ListRecords
{
    protected static string $resource = InternalPageHeaderResource::class;

    protected static ?string $title = 'هيدرات الصفحات الداخلية';

    protected static ?string $breadcrumb = 'هيدرات الصفحات الداخلية';

    public function mount(): void
    {
        InternalPageHeader::syncConfiguredSections();

        parent::mount();
    }

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
