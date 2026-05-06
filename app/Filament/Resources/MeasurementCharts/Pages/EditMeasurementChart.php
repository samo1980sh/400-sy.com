<?php

namespace App\Filament\Resources\MeasurementCharts\Pages;

use App\Filament\Resources\MeasurementCharts\MeasurementChartResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMeasurementChart extends EditRecord
{
    protected static string $resource = MeasurementChartResource::class;
    protected static ?string $title = 'تعديل صف قياس';
    protected static ?string $breadcrumb = 'صفوف القياس';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
