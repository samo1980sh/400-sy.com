<?php

namespace App\Filament\Resources\MeasurementChartGroups\Pages;

use App\Filament\Resources\MeasurementChartGroups\MeasurementChartGroupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMeasurementChartGroup extends EditRecord
{
    protected static string $resource = MeasurementChartGroupResource::class;
    protected static ?string $title = 'تعديل مجموعة قياس';
    protected static ?string $breadcrumb = 'مجموعات القياس';

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
