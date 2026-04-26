<?php

namespace App\Filament\Resources\MeasurementCharts\Tables;

use App\Filament\Resources\MeasurementCharts\Schemas\MeasurementChartForm;
use App\Models\MeasurementChart;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class MeasurementChartsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('size_code')
                    ->label('القياس')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('chest')
                    ->label('الصدر')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => blank($state) ? '-' : number_format((float) $state, 2, '.', ',')),
                TextColumn::make('shoulder')
                    ->label('الكتف')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => blank($state) ? '-' : number_format((float) $state, 2, '.', ',')),
                TextColumn::make('waist')
                    ->label('الوسط')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => blank($state) ? '-' : number_format((float) $state, 2, '.', ',')),
                TextColumn::make('length')
                    ->label('الطول')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => blank($state) ? '-' : number_format((float) $state, 2, '.', ',')),
                TextColumn::make('sleeve')
                    ->label('الكم')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => blank($state) ? '-' : number_format((float) $state, 2, '.', ',')),
                TextColumn::make('collar')
                    ->label('الياقة')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => blank($state) ? '-' : number_format((float) $state, 2, '.', ',')),
                TextColumn::make('inside_leg')
                    ->label('وسط الرجل')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => blank($state) ? '-' : number_format((float) $state, 2, '.', ',')),
                TextColumn::make('waistline')
                    ->label('الخاصرة')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => blank($state) ? '-' : number_format((float) $state, 2, '.', ',')),
                TextColumn::make('thigh_width')
                    ->label('عرض الفخذ')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => blank($state) ? '-' : number_format((float) $state, 2, '.', ',')),
                TextColumn::make('leg_width')
                    ->label('عرض الرجل')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => blank($state) ? '-' : number_format((float) $state, 2, '.', ',')),
                TextColumn::make('leg_length')
                    ->label('طول الرجل')
                    ->sortable()
                    ->formatStateUsing(fn ($state): string => blank($state) ? '-' : number_format((float) $state, 2, '.', ',')),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('editMeasurementChart')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل زمرة وحدة قياس')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(fn (MeasurementChart $record) => MeasurementChartForm::components())
                    ->fillForm(fn (MeasurementChart $record): array => $record->only([
                        'name',
                        'size_code',
                        'chest',
                        'shoulder',
                        'waist',
                        'length',
                        'sleeve',
                        'collar',
                        'inside_leg',
                        'waistline',
                        'thigh_width',
                        'leg_width',
                        'leg_length',
                    ]))
                    ->action(function (MeasurementChart $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث الزمرة بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث الزمرة.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف')
                        ->icon(Heroicon::OutlinedTrash),
                ]),
            ]);
    }
}
