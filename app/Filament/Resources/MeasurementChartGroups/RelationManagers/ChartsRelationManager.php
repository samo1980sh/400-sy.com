<?php

namespace App\Filament\Resources\MeasurementChartGroups\RelationManagers;

use App\Models\MeasurementChart;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class ChartsRelationManager extends RelationManager
{
    protected static string $relationship = 'charts';
    protected static ?string $title = 'صفوف القياس';

    public function form(Schema $schema): Schema
    {
        return $schema->components($this->chartFormComponents());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('size_code')
                    ->label('القياس')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('chest')->label('الصدر')->sortable(),
                TextColumn::make('shoulder')->label('الكتف')->sortable(),
                TextColumn::make('waist')->label('الوسط')->sortable(),
                TextColumn::make('length')->label('الطول')->sortable(),
                TextColumn::make('sleeve')->label('الكم')->sortable(),
                TextColumn::make('collar')->label('الياقة')->sortable(),
                TextColumn::make('inside_leg')->label('وسط الرجل')->sortable(),
                TextColumn::make('waistline')->label('الخاصرة')->sortable(),
                TextColumn::make('thigh_width')->label('عرض الفخذ')->sortable(),
                TextColumn::make('leg_width')->label('عرض الرجل')->sortable(),
                TextColumn::make('leg_length')->label('طول الرجل')->sortable(),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->headerActions([
                Action::make('createChart')
                    ->label('إضافة صف قياس')
                    ->icon(Heroicon::OutlinedPlusCircle)
                    ->color('primary')
                    ->modalHeading('إضافة صف قياس')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema($this->chartFormComponents())
                    ->action(function (array $data): void {
                        $data['measurement_chart_group_id'] = $this->getOwnerRecord()->id;
                        $data['name'] = $this->getOwnerRecord()->name;

                        MeasurementChart::create($data);
                    }),
            ])
            ->recordActions([
                Action::make('editChart')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->color('gray')
                    ->modalHeading('تعديل صف قياس')
                    ->modalSubmitActionLabel('حفظ')
                    ->modalWidth('4xl')
                    ->schema($this->chartFormComponents())
                    ->fillForm(fn (MeasurementChart $record): array => $record->only([
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
                        $data['measurement_chart_group_id'] = $this->getOwnerRecord()->id;
                        $data['name'] = $this->getOwnerRecord()->name;
                        $record->update($data);
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف'),
                ]),
            ]);
    }

    protected function chartFormComponents(): array
    {
        return [
            TextInput::make('size_code')
                ->label('القياس')
                ->required()
                ->maxLength(50),
            TextInput::make('chest')->label('الصدر')->numeric()->default(null),
            TextInput::make('shoulder')->label('الكتف')->numeric()->default(null),
            TextInput::make('waist')->label('الوسط')->numeric()->default(null),
            TextInput::make('length')->label('الطول')->numeric()->default(null),
            TextInput::make('sleeve')->label('الكم')->numeric()->default(null),
            TextInput::make('collar')->label('الياقة')->numeric()->default(null),
            TextInput::make('inside_leg')->label('وسط الرجل')->numeric()->default(null),
            TextInput::make('waistline')->label('الخاصرة')->numeric()->default(null),
            TextInput::make('thigh_width')->label('عرض الفخذ')->numeric()->default(null),
            TextInput::make('leg_width')->label('عرض الرجل')->numeric()->default(null),
            TextInput::make('leg_length')->label('طول الرجل')->numeric()->default(null),
        ];
    }
}
