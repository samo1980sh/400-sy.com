<?php

namespace App\Filament\Resources\ImportBatches\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImportBatchesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('النوع')
                    ->searchable(),
                TextColumn::make('source_file')
                    ->label('الملف المصدر')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
                TextColumn::make('started_at')
                    ->label('بدأ في')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('finished_at')
                    ->label('انتهى في')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_by')
                    ->label('أنشأه')
                    ->numeric()
                    ->sortable(),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
