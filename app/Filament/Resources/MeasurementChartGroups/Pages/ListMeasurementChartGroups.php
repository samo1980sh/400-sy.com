<?php

namespace App\Filament\Resources\MeasurementChartGroups\Pages;

use App\Filament\Resources\MeasurementChartGroups\MeasurementChartGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMeasurementChartGroups extends ListRecords
{
    protected static string $resource = MeasurementChartGroupResource::class;
    protected static ?string $title = 'مجموعات القياس';
    protected static ?string $breadcrumb = 'مجموعات القياس';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة مجموعة قياس'),
        ];
    }
}
