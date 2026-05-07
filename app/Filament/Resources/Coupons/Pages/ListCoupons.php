<?php

namespace App\Filament\Resources\Coupons\Pages;

use App\Filament\Resources\Coupons\CouponResource;
use App\Services\SalesSettingsExportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListCoupons extends ListRecords
{
    protected static string $resource = CouponResource::class;
    protected static ?string $title = 'الكوبونات';
    protected static ?string $breadcrumb = 'الكوبونات';
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
