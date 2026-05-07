<?php

namespace App\Filament\Resources\WarehouseInventoryItems\Tables;

use App\Models\WarehouseInventoryItem;
use App\Services\WarehouseExcelImportService;
use App\Services\WarehouseExportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Throwable;

class WarehouseInventoryItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->withCount('balances'))
            ->columns([
                TextColumn::make('short_code')
                    ->label('الكود المختصر')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('model_code')
                    ->label('رمز الموديل')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('item_name')
                    ->label('اسم الصنف')
                    ->searchable(),

                TextColumn::make('size_code')
                    ->label('القياس')
                    ->searchable()
                    ->badge(),

                TextColumn::make('color_name')
                    ->label('اللون')
                    ->searchable(),

                TextColumn::make('color_code')
                    ->label('رقم اللون')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('warehouse_stock')
                    ->label('مخزن')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),

                TextColumn::make('balances_count')
                    ->label('عدد الصالات')
                    ->badge()
                    ->state(fn (WarehouseInventoryItem $record): int => (int) ($record->balances_count ?? 0)),

                TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('size_code')
                    ->label('القياس')
                    ->options(fn (): array => WarehouseInventoryItem::query()
                        ->whereNotNull('size_code')
                        ->where('size_code', '<>', '')
                        ->distinct()
                        ->orderBy('size_code')
                        ->pluck('size_code', 'size_code')
                        ->all())
                    ->searchable()
                    ->preload(),

                SelectFilter::make('color_name')
                    ->label('اللون')
                    ->options(fn (): array => WarehouseInventoryItem::query()
                        ->whereNotNull('color_name')
                        ->where('color_name', '<>', '')
                        ->distinct()
                        ->orderBy('color_name')
                        ->pluck('color_name', 'color_name')
                        ->all())
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Action::make('importWarehouseInventory')
                    ->label('استيراد المخزون')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->color('primary')
                    ->modalHeading('استيراد مخزون المستودعات')
                    ->modalSubmitActionLabel('استيراد')
                    ->modalWidth('4xl')
                    ->schema([
                        FileUpload::make('file')
                            ->label('ملف Excel')
                            ->required()
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                            ])
                            ->directory('warehouse-imports')
                            ->storeFiles(),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $path = $data['file'] ?? null;

                            if (! is_string($path) || $path === '') {
                                throw new \RuntimeException('لم يتم اختيار ملف.');
                            }

                            $summary = app(WarehouseExcelImportService::class)->import(Storage::path($path));

                            Notification::make()
                                ->title('تم استيراد مخزون المستودعات بنجاح.')
                                ->body('صالات جديدة: ' . ($summary['halls_created'] ?? 0) . ' - عناصر جديدة: ' . ($summary['items_created'] ?? 0))
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل استيراد المخزون.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                Action::make('exportWarehouse')
                    ->label('تصدير')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('success')
                    ->action(fn () => app(WarehouseExportService::class)->download()),
            ]);
    }
}
