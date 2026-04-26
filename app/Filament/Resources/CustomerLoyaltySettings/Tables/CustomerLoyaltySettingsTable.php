<?php

namespace App\Filament\Resources\CustomerLoyaltySettings\Tables;

use App\Filament\Resources\CustomerLoyaltySettings\Schemas\CustomerLoyaltySettingForm;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomerLoyaltySettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                IconColumn::make('enabled')
                    ->label('فعال')
                    ->boolean(),
                TextColumn::make('award_on_status')
                    ->label('عند الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'delivered' => 'مُسلم',
                        'paid' => 'مدفوع',
                        'confirmed' => 'مؤكد',
                        default => (string) $state,
                    }),
                TextColumn::make('points_base')
                    ->label('الأساس')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'net_total' => 'الصافي بعد الحسم',
                        'grand_total' => 'الإجمالي النهائي',
                        default => (string) $state,
                    }),
                TextColumn::make('points_per_currency')
                    ->label('نقاط لكل عملة')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 4, '.', ',')),
                TextColumn::make('updated_at')
                    ->label('آخر تحديث')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('تعديل')
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->modalWidth('4xl')
                    ->schema(CustomerLoyaltySettingForm::components())
                    ->fillForm(fn ($record): array => [
                        'enabled' => $record->enabled,
                        'award_on_status' => $record->award_on_status,
                        'points_base' => $record->points_base,
                        'points_per_currency' => $record->points_per_currency,
                        'notes' => $record->notes,
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update($data);
                    }),
            ])
            ->toolbarActions([]);
    }
}
