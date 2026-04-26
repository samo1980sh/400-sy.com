<?php

namespace App\Filament\Resources\ProductWholesaleAvailabilities\Pages;

use App\Filament\Resources\ProductWholesaleAvailabilities\ProductWholesaleAvailabilityResource;
use App\Filament\Resources\ProductWholesaleAvailabilities\Schemas\ProductWholesaleAvailabilityForm;
use App\Models\ImportBatch;
use App\Models\ProductWholesaleAvailability;
use App\Services\ProductWholesaleAvailabilityImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class ListProductWholesaleAvailabilities extends ListRecords
{
    protected static string $resource = ProductWholesaleAvailabilityResource::class;

    protected static ?string $title = 'توافر التاجر';

    protected static ?string $breadcrumb = 'توافر التاجر';

    protected function getHeaderActions(): array
    {
        return [
            $this->purgeAction(),
            $this->importAction(),
            $this->createAction(),
        ];
    }

    protected function purgeAction(): Action
    {
        return Action::make('purgeWholesaleAvailability')
            ->label('تفريغ')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('تفريغ توافر التاجر')
            ->modalDescription('سيتم حذف جميع سجلات توافر التاجر وإعادة ترقيم الجدول من 1.')
            ->modalSubmitActionLabel('تفريغ')
            ->action(function (): void {
                ProductWholesaleAvailability::query()->delete();
                DB::statement('ALTER TABLE product_wholesale_availabilities AUTO_INCREMENT = 1');

                Notification::make()
                    ->title('تم تفريغ توافر التاجر بنجاح.')
                    ->success()
                    ->send();
            });
    }

    protected function importAction(): Action
    {
        return Action::make('importWholesaleAvailability')
            ->label('استيراد')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->modalHeading('استيراد توافر التاجر')
            ->modalDescription('ارفع ملف saller_sizes-syria.csv الخاص بالتاجر ثم نفّذ الاستيراد.')
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

                $batch = ImportBatch::create([
                    'type' => 'wholesale-availability-import',
                    'source_file' => basename($path),
                    'status' => 'running',
                    'started_at' => Carbon::now(),
                    'created_by' => auth()->id(),
                    'note' => 'Wholesale availability import started from Filament.',
                ]);

                try {
                    $summary = app(ProductWholesaleAvailabilityImportService::class)->import($path);

                    $batch->fill([
                        'status' => 'completed',
                        'finished_at' => Carbon::now(),
                        'note' => implode("\n", [
                            'اكتمل استيراد توافر التاجر.',
                            'المدخلة: ' . ($summary['created'] ?? 0),
                            'المحدثة: ' . ($summary['updated'] ?? 0),
                            'المتجاوزة: ' . ($summary['skipped'] ?? 0),
                            'المتجاوزة لأنها ليست تاجرًا: ' . ($summary['skipped_non_wholesale'] ?? 0),
                            'المتجاوزة لبيانات ناقصة: ' . ($summary['skipped_missing_data'] ?? 0),
                        ]),
                    ])->save();

                    Notification::make()
                        ->title('اكتمل استيراد توافر التاجر بنجاح.')
                        ->body(
                            'المدخلة: ' . ($summary['created'] ?? 0) .
                            ' | المحدثة: ' . ($summary['updated'] ?? 0) .
                            ' | ليست تاجرًا: ' . ($summary['skipped_non_wholesale'] ?? 0) .
                            ' | بيانات ناقصة: ' . ($summary['skipped_missing_data'] ?? 0),
                        )
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    $batch->fill([
                        'status' => 'failed',
                        'finished_at' => Carbon::now(),
                        'note' => $exception->getMessage(),
                    ])->save();

                    report($exception);

                    Notification::make()
                        ->title('فشل استيراد توافر التاجر.')
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

    protected function createAction(): Action
    {
        return Action::make('createWholesaleAvailability')
            ->label('إضافة توافر')
            ->icon(Heroicon::OutlinedPlusCircle)
            ->color('primary')
            ->modalHeading('إضافة توافر التاجر')
            ->modalSubmitActionLabel('حفظ')
            ->modalWidth('4xl')
            ->schema(ProductWholesaleAvailabilityForm::components())
            ->action(function (array $data): void {
                try {
                    ProductWholesaleAvailability::create($data);

                    Notification::make()
                        ->title('تمت إضافة توافر التاجر بنجاح.')
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('فشل إضافة توافر التاجر.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
