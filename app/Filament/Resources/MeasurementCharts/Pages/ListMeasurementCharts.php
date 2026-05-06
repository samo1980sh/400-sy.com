<?php

namespace App\Filament\Resources\MeasurementCharts\Pages;

use App\Filament\Resources\MeasurementCharts\MeasurementChartResource;
use App\Filament\Resources\MeasurementCharts\Schemas\MeasurementChartForm;
use App\Models\MeasurementChart;
use App\Models\MeasurementChartGroup;
use App\Services\MeasurementChartImportService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms\Components\FileUpload;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class ListMeasurementCharts extends ListRecords
{
    protected static string $resource = MeasurementChartResource::class;
    protected static ?string $title = 'صفوف القياس';
    protected static ?string $breadcrumb = 'صفوف القياس';

    protected function getHeaderActions(): array
    {
        return [
            $this->importAction(),
            $this->createAction(),
        ];
    }

    protected function createAction(): Action
    {
        return Action::make('createMeasurementChart')
            ->label('إضافة صف قياس')
            ->icon(Heroicon::OutlinedPlusCircle)
            ->color('primary')
            ->modalHeading('إضافة صف قياس')
            ->modalSubmitActionLabel('حفظ')
            ->modalWidth('4xl')
            ->schema(MeasurementChartForm::components())
            ->action(function (array $data): void {
                try {
                    $group = MeasurementChartGroup::find($data['measurement_chart_group_id'] ?? null);
                    $data['name'] = $group?->name;
                    MeasurementChart::create($data);

                    Notification::make()
                        ->title('تمت إضافة صف القياس بنجاح.')
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('فشل إضافة صف القياس.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected function importAction(): Action
    {
        return Action::make('importMeasurementCharts')
            ->label('استيراد')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->modalHeading('استيراد صفوف القياس')
            ->modalDescription('ارفع ملف CSV أو Excel يحتوي على بيانات صفوف القياس ثم نفّذ الاستيراد.')
            ->modalSubmitActionLabel('استيراد')
            ->modalWidth('xl')
            ->schema([
                FileUpload::make('source_file')
                    ->label('ملف الاستيراد')
                    ->required()
                    ->storeFiles(false)
                    ->visibility('private')
                    ->acceptedFileTypes([
                        'text/csv',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                    ]),
            ])
            ->action(function (array $data): void {
                $path = $this->resolveUploadedPath($data['source_file'] ?? null);

                if (! $path) {
                    Notification::make()
                        ->title('الملف غير موجود أو غير قابل للقراءة.')
                        ->danger()
                        ->send();

                    return;
                }

                try {
                    $summary = app(MeasurementChartImportService::class)->import($path);

                    Notification::make()
                        ->title('تم الاستيراد بنجاح.')
                        ->body(
                            'المضافة: ' . ($summary['created'] ?? 0) .
                            ' | المحدّثة: ' . ($summary['updated'] ?? 0) .
                            ' | المتجاوزة: ' . ($summary['skipped'] ?? 0),
                        )
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('فشل الاستيراد.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected function resolveUploadedPath(mixed $file): ?string
    {
        if ($file instanceof TemporaryUploadedFile) {
            return $file->getRealPath() ?: $file->getPathname();
        }

        if (is_string($file) && $file !== '') {
            return $file;
        }

        return null;
    }
}
