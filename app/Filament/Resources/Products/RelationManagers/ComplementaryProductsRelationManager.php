<?php

declare(strict_types=1);

namespace App\Filament\Resources\Products\RelationManagers;

use App\Services\ComplementaryProductsExportService;
use App\Services\RetailExcelImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class ComplementaryProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'complements';

    protected static ?string $title = 'المنتجات المكملة';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ! (bool) ($ownerRecord->show_wholesale ?? false);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query
                    ->with('relatedProduct')
                    ->whereHas('relatedProduct', function ($query) {
                        $query
                            ->where('is_active', true)
                            ->where(function ($query) {
                                $query
                                    ->whereDoesntHave('variants')
                                    ->orWhereHas('variants', function ($query) {
                                        $query->where('quantity', '>', 0);
                                    });
                            });
                    });
            })
            ->defaultSort('sort_order')
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->badge()
                    ->sortable(),

                TextColumn::make('relatedProduct.model_no')
                    ->label('رمز الموديل')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('relatedProduct.title_ar')
                    ->label('الاسم بالعربي')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('relatedProduct.title_en')
                    ->label('الاسم بالانكليزي')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('relatedProduct.is_active')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'فعال' : 'معطل'),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->emptyStateHeading('لا توجد منتجات مكملة')
            ->emptyStateDescription('لم يتم العثور على منتجات مكملة متوفرة أو قابلة للعرض لهذا المنتج.')
            ->headerActions([
                Action::make('exportComplementaryProducts')
                    ->label('تصدير')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('success')
                    ->action(fn () => app(ComplementaryProductsExportService::class)->download()),

                Action::make('importComplementaryProducts')
                    ->label('استيراد')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->color('gray')
                    ->modalHeading('استيراد المنتجات المكملة')
                    ->modalDescription('ارفع ملف Excel الذي يحتوي رموز المنتجات المكملة ثم نفّذ الاستيراد.')
                    ->modalSubmitActionLabel('استيراد')
                    ->modalWidth('xl')
                    ->schema([
                        FileUpload::make('source_file')
                            ->label('ملف الاستيراد')
                            ->required()
                            ->storeFiles(false)
                            ->visibility('private')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
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
                            $summary = app(RetailExcelImportService::class)->importComplementaryProductsFile($path);

                            Notification::make()
                                ->title('تم استيراد المنتجات المكملة بنجاح.')
                                ->body(
                                    'المعالجة: ' . ($summary['synced'] ?? 0) .
                                    ' | المتجاوزة: ' . ($summary['skipped'] ?? 0),
                                )
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل استيراد المنتجات المكملة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
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
