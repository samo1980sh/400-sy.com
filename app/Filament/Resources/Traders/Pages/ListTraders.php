<?php

namespace App\Filament\Resources\Traders\Pages;

use App\Filament\Resources\Traders\TraderResource;
use App\Services\TraderExportService;
use App\Services\TraderImportService;
use App\Services\TraderTemplateService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class ListTraders extends ListRecords
{
    protected static string $resource = TraderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->templateAction(),
            $this->importAction(),
            $this->exportAction(),
            CreateAction::make(),
        ];
    }

    protected function templateAction(): Action
    {
        return Action::make('downloadTraderImportTemplate')
            ->label('قالب الاستيراد')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('warning')
            ->action(fn () => app(TraderTemplateService::class)->download());
    }

    protected function importAction(): Action
    {
        return Action::make('importTraders')
            ->label('استيراد')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->modalHeading('استيراد التجار')
            ->modalDescription('ارفع ملف Excel يحتوي بيانات التجار. سيتم تحديث التاجر إذا تطابق رقم الحساب أو البريد أو الموبايل، وإضافة سجل جديد عند عدم وجود تطابق.')
            ->modalSubmitActionLabel('استيراد')
            ->modalWidth('xl')
            ->schema([
                FileUpload::make('source_file')
                    ->label('ملف Excel')
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
                    $summary = app(TraderImportService::class)->import($path);
                    $errors = $summary['errors'] ?? [];

                    Notification::make()
                        ->title('تم استيراد التجار بنجاح.')
                        ->body(
                            'المضافة: ' . ($summary['created'] ?? 0) .
                            ' | المحدثة: ' . ($summary['updated'] ?? 0) .
                            ' | المتجاوزة: ' . ($summary['skipped'] ?? 0) .
                            ' | فئات جديدة: ' . ($summary['groups_created'] ?? 0) .
                            ($errors !== [] ? "\n" . implode("\n", $errors) : ''),
                        )
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('فشل استيراد التجار.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected function exportAction(): Action
    {
        return Action::make('exportTraders')
            ->label('تصدير')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('success')
            ->action(fn () => app(TraderExportService::class)->download());
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
