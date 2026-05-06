<?php

namespace App\Filament\Resources\MeasurementCharts\Pages;

use App\Filament\Resources\MeasurementCharts\MeasurementChartResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMeasurementChart extends CreateRecord
{
    protected static string $resource = MeasurementChartResource::class;
    protected static ?string $title = 'إضافة صف قياس';
    protected static ?string $breadcrumb = 'صفوف القياس';
}
