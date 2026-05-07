<?php

namespace App\Filament\Resources\ContactInfoSettings\Pages;

use App\Filament\Resources\ContactInfoSettings\ContactInfoSettingResource;
use App\Models\ContactInfoSetting;
use App\Services\CompanyContentExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListContactInfoSettings extends ListRecords
{
    protected static string $resource = ContactInfoSettingResource::class;

    protected static ?string $title = 'معلومات اتصال عامة';

    protected static ?string $breadcrumb = 'معلومات اتصال عامة';

    public function mount(): void
    {
        ContactInfoSetting::singleton();

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
