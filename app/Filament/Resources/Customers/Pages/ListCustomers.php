<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Filament\Resources\Customers\CustomerResource;
use App\Services\CustomerExportService;
use App\Services\CustomerImportService;
use App\Services\CustomerTemplateService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected static ?string $title = 'الزبائن';

    protected static ?string $breadcrumb = 'الزبائن';

    protected function getHeaderActions(): array
    {
        return [
            $this->templateAction(),
            $this->importAction(),
            $this->exportAction(),
        ];
    }

    protected function templateAction(): Action
    {
        return Action::make('downloadCustomerImportTemplate')
            ->label('قالب الاستيراد')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('warning')
            ->action(fn () => app(CustomerTemplateService::class)->download());
    }

    protected function importAction(): Action
    {
        return Action::make('importCustomers')
            ->label('استيراد الزبائن')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->modalHeading('استيراد الزبائن من Excel')
            ->modalDescription('يُعتمد رقم الحساب الموجود في الملف حرفيًا. يتم تحديث الزبون عند تطابق رقم الحساب، وإنشاء زبون جديد عند عدم وجوده. لا تُستورد كلمات المرور، ويمكن للزبون المستورد تفعيل حسابه من الموقع إذا كان لديه بريد إلكتروني صحيح.')
            ->modalSubmitActionLabel('استيراد')
            ->modalWidth('xl')
            ->schema([
                FileUpload::make('source_file')
                    ->label('ملف Excel')
                    ->required()
                    ->storeFiles(false)
                    ->visibility('private')
                    ->acceptedFileTypes([
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
                    $summary = app(CustomerImportService::class)->import($path);
                    $errors = $summary['errors'] ?? [];
                    $skipped = (int) ($summary['skipped'] ?? 0);

                    $notification = Notification::make()
                        ->title($skipped > 0 ? 'اكتمل استيراد الزبائن مع وجود أسطر متجاوزة.' : 'تم استيراد الزبائن بنجاح.')
                        ->body(
                            'المضافة: ' . ($summary['created'] ?? 0) .
                            ' | المحدثة: ' . ($summary['updated'] ?? 0) .
                            ' | المتجاوزة: ' . $skipped .
                            ($errors !== [] ? "\n" . implode("\n", $errors) : '')
                        );

                    if ($skipped > 0) {
                        $notification->warning()->persistent();
                    } else {
                        $notification->success();
                    }

                    $notification->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title('فشل استيراد الزبائن.')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    protected function exportAction(): Action
    {
        return Action::make('exportCustomers')
            ->label('تصدير')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('success')
            ->action(fn () => app(CustomerExportService::class)->download());
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
