<?php

namespace App\Filament\Resources\CouponSettings\Tables;

use App\Filament\Resources\CouponSettings\Schemas\CouponSettingForm;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CouponSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                IconColumn::make('enabled')
                    ->label('مفعّل')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(80),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->modalWidth('4xl')
                    ->schema(CouponSettingForm::components())
                    ->fillForm(fn ($record): array => [
                        'enabled' => $record->enabled,
                        'notes' => $record->notes,
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update($data);
                    }),
            ])
            ->toolbarActions([]);
    }
}
