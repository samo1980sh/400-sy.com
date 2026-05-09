<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\ImportBatch;
use App\Services\ProductCatalogExportService;
use App\Services\RetailExcelImportService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;
    protected static ?string $title = 'المنتجات';
    protected static ?string $breadcrumb = 'المنتجات';

    protected function getHeaderActions(): array
    {
        return [
            $this->purgeAction(),
            $this->importAction(),
            $this->exportAction(),
            CreateAction::make()
                ->label('إضافة منتج')
                ->icon(Heroicon::OutlinedPlusCircle),
        ];
    }

    protected function purgeAction(): Action
    {
        return Action::make('purgeProducts')
            ->label('تفريغ')
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('تفريغ قسم المنتجات')
            ->modalDescription('سيتم حذف المنتجات والبيانات المرتبطة بالاستيراد وإعادة ترقيم الجداول من 1.')
            ->modalSubmitActionLabel('تفريغ')
            ->action(function (): void {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');

                DB::table('product_wholesale_availabilities')->truncate();
                DB::table('product_wholesale_group_assignments')->truncate();
                DB::table('product_retail_group_assignments')->truncate();
                DB::table('product_wholesale_colors')->truncate();
                DB::table('product_wholesale_quantities')->truncate();
                DB::table('product_variants')->truncate();
                DB::table('product_colors')->truncate();
                DB::table('products')->truncate();
                DB::table('import_rows')->truncate();
                ImportBatch::truncate();

                DB::statement('SET FOREIGN_KEY_CHECKS=1');

                Notification::make()
                    ->title('تم تفريغ قسم المنتجات بنجاح.')
                    ->success()
                    ->send();
            });
    }

    protected function importAction(): Action
    {
        return Action::make('importProducts')
            ->label('استيراد')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->modalHeading('استيراد ملف المنتجات الرئيسي')
            ->modalDescription('ارفع ملف المنتجات الرئيسي ثم نفّذ الاستيراد. سيتم حفظ المنتجات فقط من هذا الملف.')
            ->modalSubmitActionLabel('استيراد')
            ->modalWidth('xl')
            ->schema([
                FileUpload::make('products_file')
                    ->label('ملف المنتجات الرئيسي')
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
                $productsPath = $this->resolveUploadedPath($data['products_file'] ?? null);

                if (! $productsPath) {
                    Notification::make()
                        ->title('الملف غير موجود أو غير قابل للقراءة.')
                        ->danger()
                        ->send();

                    return;
                }

                $batch = ImportBatch::create([
                    'type' => 'products-import',
                    'source_file' => basename($productsPath),
                    'status' => 'running',
                    'started_at' => Carbon::now(),
                    'created_by' => auth()->id(),
                    'note' => 'Products import started from Filament.',
                ]);

                try {
                    $summary = app(RetailExcelImportService::class)->importProductsFile($productsPath, $batch->id);

                    $batch->fill([
                        'status' => 'completed',
                        'finished_at' => Carbon::now(),
                        'note' => implode("\n", [
                            'اكتمل استيراد المنتجات.',
                            'إجمالي المنتجات المستوردة: ' . ($summary['products_imported'] ?? 0),
                            'المنتجات المنشأة: ' . ($summary['products_created'] ?? 0),
                            'المنتجات المحدثة: ' . ($summary['products_updated'] ?? 0),
                            'الألوان الجديدة من عمود التركيب: ' . ($summary['new_structure_colors_created'] ?? 0),
                            'السطور ذات تركيب فارغ: ' . ($summary['empty_structure_rows'] ?? 0),
                            'السطور ذات تركيب مركب: ' . ($summary['composite_structure_rows'] ?? 0),
                            'المنتجات المتجاوزة: ' . ($summary['products_skipped'] ?? 0),
                        ]),
                    ])->save();

                    Notification::make()
                        ->title('اكتمل استيراد المنتجات بنجاح.')
                        ->body(implode(' | ', [
                            'إجمالي المنتجات: ' . ($summary['products_imported'] ?? 0),
                            'ألوان جديدة: ' . ($summary['new_structure_colors_created'] ?? 0),
                            'تركيب فارغ: ' . ($summary['empty_structure_rows'] ?? 0),
                            'تركيب مركب: ' . ($summary['composite_structure_rows'] ?? 0),
                            'منتجات متجاوزة: ' . ($summary['products_skipped'] ?? 0),
                        ]))
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
                        ->title('فشل استيراد المنتجات.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected function exportAction(): Action
    {
        return Action::make('exportProducts')
            ->label('تصدير')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('success')
            ->action(fn () => app(ProductCatalogExportService::class)->download());
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
