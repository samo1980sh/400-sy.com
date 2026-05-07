<?php

namespace App\Filament\Resources\MeasurementChartGroups\Pages;

use App\Filament\Resources\MeasurementChartGroups\MeasurementChartGroupResource;
use App\Filament\Resources\MeasurementChartGroups\Schemas\MeasurementChartGroupForm;
use App\Models\MeasurementChartGroup;
use App\Services\MeasurementChartExportService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ListMeasurementChartGroups extends ListRecords
{
    protected static string $resource = MeasurementChartGroupResource::class;
    protected static ?string $title = 'مجموعات القياس';
    protected static ?string $breadcrumb = 'مجموعات القياس';

    protected function getHeaderActions(): array
    {
        return [
            $this->exportAction(),
            Action::make('createMeasurementChartGroup')
                ->label('إضافة مجموعة قياس')
                ->icon(Heroicon::OutlinedPlusCircle)
                ->color('primary')
                ->modalHeading('إضافة مجموعة قياس')
                ->modalSubmitActionLabel('حفظ')
                ->modalWidth('4xl')
                ->schema(MeasurementChartGroupForm::components())
                ->action(function (array $data): void {
                    try {
                        MeasurementChartGroup::create($data);

                        Notification::make()
                            ->title('تمت إضافة مجموعة القياس بنجاح.')
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('فشل إضافة مجموعة القياس.')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function exportAction(): Action
    {
        return Action::make('exportMeasurementCharts')
            ->label('تصدير')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('success')
            ->action(fn () => app(MeasurementChartExportService::class)->download());
    }
}
