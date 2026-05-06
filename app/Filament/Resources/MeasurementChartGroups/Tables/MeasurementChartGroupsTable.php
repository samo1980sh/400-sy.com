<?php

namespace App\Filament\Resources\MeasurementChartGroups\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف'),
                ]),
            ]);
    }
}
