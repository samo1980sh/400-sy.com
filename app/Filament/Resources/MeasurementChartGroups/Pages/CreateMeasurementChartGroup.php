<?php

namespace App\Filament\Resources\MeasurementChartGroups\Pages;

use App\Filament\Resources\MeasurementChartGroups\MeasurementChartGroupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMeasurementChartGroup extends CreateRecord
{
    protected static string $resource = MeasurementChartGroupResource::class;
    protected static ?string $title = 'إضافة مجموعة قياس';
    protected static ?string $breadcrumb = 'مجموعات القياس';
}
