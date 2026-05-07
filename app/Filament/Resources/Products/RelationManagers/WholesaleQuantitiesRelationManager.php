<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WholesaleQuantitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'wholesaleSeries';

    protected static ?string $title = 'سيريات الجملة';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return (bool) ($ownerRecord->show_wholesale ?? false);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_wholesale_color_id')
                ->label('لون الجملة')
                ->options(function (): array {
                    return $this->getOwnerRecord()
                        ->wholesaleColors()
                        ->orderBy('color_name_ar')
                        ->get()
                        ->mapWithKeys(fn ($color): array => [
                            $color->id => trim(implode(' — ', array_filter([
                                $color->color_code,
                                $color->color_name_ar,
                            ]))),
                        ])
                        ->all();
                })
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('series_group')
                ->label('مجموعة السيرية')
                ->numeric()
                ->required()
                ->default(1)
                ->minValue(1),
            TextInput::make('size_text')
                ->label('نص السيرية')
                ->required()
                ->maxLength(100),
            TextInput::make('quantity')
                ->label('الكمية')
                ->numeric()
                ->required(),
            TextInput::make('source_value')
                ->label('القيمة الخام')
                ->maxLength(255)
                ->default(null),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('series_group')
            ->recordTitleAttribute('size_text')
            ->columns([
                TextColumn::make('wholesaleColor.color_name_ar')
                    ->label('لون الجملة')
                    ->formatStateUsing(fn ($state, $record): string => trim(implode(' — ', array_filter([
                        $record?->wholesaleColor?->color_code,
                        $record?->wholesaleColor?->color_name_ar,
                    ]))) ?: '-')
                    ->sortable(),
                TextColumn::make('series_group')
                    ->label('مجموعة السيرية')
                    ->badge()
                    ->sortable(),
                TextColumn::make('size_text')
                    ->label('نص السيرية')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 0, '.', ','))
                    ->sortable(),
                TextColumn::make('source_value')
                    ->label('النص الخام')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة'))
            ->headerActions([
                CreateAction::make()->label('إضافة سيريّة جملة'),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('حذف'),
                ]),
            ]);
    }
}
