<?php

namespace App\Filament\Resources\MeasurementChartGroups\Tables;

use App\Filament\Resources\MeasurementChartGroups\Schemas\MeasurementChartGroupForm;
use App\Models\MeasurementChartGroup;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class MeasurementChartGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('charts'))
            ->columns([
                ImageColumn::make('guide_image')
                    ->label('الصورة')
                    ->disk('public')
                    ->height(48)
                    ->width(48)
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('اسم المجموعة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('charts_count')
                    ->label('عدد القياسات')
                    ->counts('charts')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('editMeasurementChartGroup')
                    ->label('تعديل')
                    ->color('gray')
                    ->modalHeading('تعديل مجموعة قياس')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema(fn (MeasurementChartGroup $record): array => array_merge(
                        MeasurementChartGroupForm::components($record),
                        [
                            SchemaView::make('filament.measurement-chart-groups.charts-preview')
                                ->columnSpanFull()
                                ->viewData([
                                    'charts' => $record->charts()
                                        ->orderBy('size_code')
                                        ->get([
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
                                        ]),
                                ]),
                        ],
                    ))
                    ->fillForm(fn (MeasurementChartGroup $record): array => $record->only([
                        'name',
                        'guide_image',
                    ]))
                    ->action(function (MeasurementChartGroup $record, array $data): void {
                        try {
                            $record->update($data);

                            Notification::make()
                                ->title('تم تحديث مجموعة القياس بنجاح.')
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->title('فشل تحديث مجموعة القياس.')
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                DeleteAction::make()
                    ->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف'),
                ]),
            ]);
    }
}
