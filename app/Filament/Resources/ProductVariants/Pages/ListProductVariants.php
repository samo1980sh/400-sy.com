<?php

namespace App\Filament\Resources\ProductVariants\Pages;

use App\Filament\Resources\ProductVariants\ProductVariantResource;
use App\Filament\Resources\ProductVariants\Schemas\ProductVariantForm;
use App\Models\ProductVariant;
use App\Services\ProductVariantExportService;
use App\Services\ProductVariantImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class ListProductVariants extends ListRecords
{
    protected static string $resource = ProductVariantResource::class;
    protected static ?string $title = 'توافر القياسات';
    protected static ?string $breadcrumb = 'توافر القياسات';

    protected function getHeaderActions(): array
    {
        return [
            $this->purgeAction(),
            $this->importAction(),
            $this->exportAction(),
            $this->createAction(),
        ];
    }

    protected function purgeAction(): Action
    {
        return Action::make('purgeProductVariants')
            ->label('تفريغ')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('تفريغ توافر القياسات')
            ->modalDescription('سيتم حذف جميع سجلات توافر القياسات وإعادة ترقيم الجدول من 1.')
            ->modalSubmitActionLabel('تفريغ')
            ->action(function (): void {
                ProductVariant::query()->delete();
                DB::statement('ALTER TABLE product_variants AUTO_INCREMENT = 1');

                Notification::make()
                    ->title('تم تفريغ توافر القياسات بنجاح.')
                    ->success()
                    ->send();
            });
    }

    protected function createAction(): Action
    {
        return Action::make('createProductVariant')
            ->label('إضافة توافر')
            ->icon(Heroicon::OutlinedPlusCircle)
            ->color('primary')
            ->modalHeading('إضافة توافر قياس')
            ->modalSubmitActionLabel('حفظ')
            ->modalWidth('4xl')
            ->schema(ProductVariantForm::components())
            ->action(function (array $data): void {
                try {
                    ProductVariant::create($data);

                    Notification::make()
                        ->title('تمت إضافة التوافر بنجاح.')
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('فشل إضافة التوافر.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected function importAction(): Action
    {
        return Action::make('importProductVariants')
            ->label('استيراد')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->modalHeading('استيراد توافر القياسات')
            ->modalDescription('ارفع ملف CSV أو Excel الخاص بتوافر قياسات منتجات المفرق ثم نفّذ الاستيراد.')
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
                    $summary = app(ProductVariantImportService::class)->import($path);

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

    protected function exportAction(): Action
    {
        return Action::make('exportProductVariants')
            ->label('تصدير')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('success')
            ->action(fn () => app(ProductVariantExportService::class)->download());
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
