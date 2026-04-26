<?php

namespace App\Filament\Resources\ExchangeRateSettings\Tables;

use App\Filament\Resources\ExchangeRateSettings\Schemas\ExchangeRateSettingForm;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ExchangeRateSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('base_currency_code')
                    ->label('العملة الأساسية')
                    ->badge(),
                IconColumn::make('show_usd')
                    ->label('USD')
                    ->boolean(),
                IconColumn::make('show_eur')
                    ->label('EUR')
                    ->boolean(),
                TextColumn::make('usd_syp_rate')
                    ->label('سعر الدولار')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 4, '.', ',')),
                TextColumn::make('eur_syp_rate')
                    ->label('سعر اليورو')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 4, '.', ',')),
                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('ملاحظات')
                    ->limit(60)
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->modalWidth('4xl')
                    ->schema(ExchangeRateSettingForm::components())
                    ->fillForm(fn ($record): array => [
                        'base_currency_code' => $record->base_currency_code,
                        'show_usd' => $record->show_usd,
                        'show_eur' => $record->show_eur,
                        'usd_syp_rate' => $record->usd_syp_rate,
                        'eur_syp_rate' => $record->eur_syp_rate,
                        'notes' => $record->notes,
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update($data);
                    }),
            ])
            ->toolbarActions([]);
    }
}
