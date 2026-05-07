<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\ProductColor;
use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\Action;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';
    protected static ?string $title = 'توافر القياسات';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return ! (bool) ($ownerRecord->show_wholesale ?? false);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('product_color_id')
                ->label('اللون')
                ->options(fn (): array => ProductColor::query()
                    ->where('product_id', $this->getOwnerRecord()->id)
                    ->get()
                    ->sortBy(fn (ProductColor $productColor): string => (string) ($productColor->color_name_ar ?? ''))
                    ->mapWithKeys(fn (ProductColor $productColor): array => [
                        $productColor->id => trim(($productColor->color_code ?: '-') . ' — ' . ($productColor->color_name_ar ?: '-')),
                    ])
                    ->all())
                ->required()
                ->searchable()
                ->preload(),
            Select::make('size_id')
                ->label('القياس')
                ->options(fn (): array => Size::query()
                    ->orderBy('sort_order')
                    ->get()
                    ->mapWithKeys(fn (Size $size): array => [
                        $size->id => trim($size->code . ' (' . ($size->name_ar ?: $size->code) . ')'),
                    ])
                    ->all())
                ->searchable()
                ->preload(),
            TextInput::make('price')
                ->label('بيع')
                ->numeric()
                ->prefix(fn (): string => $this->getOwnerRecord()?->currency_ar ?? 'SYP')
                ->required(),
            TextInput::make('compare_price')
                ->label('كرت')
                ->numeric()
                ->prefix(fn (): string => $this->getOwnerRecord()?->currency_ar ?? 'SYP'),
            TextInput::make('quantity')
                ->label('الكمية')
                ->numeric()
                ->required(),
            TextInput::make('status')
                ->label('الحالة')
                ->default('active')
                ->required()
                ->maxLength(20),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('productColor.color_code')
                    ->label('اللون')
                    ->formatStateUsing(fn ($state, $record): string => trim(($record?->productColor?->color_code ?? '-') . ' — ' . ($record?->productColor?->color_name_ar ?? '-')))
                    ->searchable(),
                TextColumn::make('size.code')
                    ->label('القياس')
                    ->formatStateUsing(fn ($state, $record): string => trim(($record?->size?->code ?? '-') . ' (' . ($record?->size?->name_ar ?? '-') . ')'))
                    ->searchable(),
                TextColumn::make('price')
                    ->label('بيع')
                    ->formatStateUsing(fn ($state, $record): string => number_format((float) $state, 2, '.', ',') . ' ' . ($record?->product?->currency_ar ?? 'SYP'))
                    ->sortable(),
                TextColumn::make('compare_price')
                    ->label('كرت')
                    ->formatStateUsing(fn ($state, $record): string => blank($state) ? '-' : number_format((float) $state, 2, '.', ',') . ' ' . ($record?->product?->currency_ar ?? 'SYP'))
                    ->sortable(),
                TextColumn::make('quantity')
                    ->label('الكمية')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 0, '.', ','))
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge(),
            ])
            ->columnManager()
            ->columnManagerTriggerAction(fn (Action $action) => $action
                ->label('إظهار / إخفاء الأعمدة')
                ->icon(Heroicon::OutlinedViewColumns))
            ->filters([])
            ->headerActions([
                CreateAction::make()->label('إضافة توافر'),
                AssociateAction::make()->label('ربط توافر'),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
                DissociateAction::make()->label('فصل'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make()->label('فصل جماعي'),
                    DeleteBulkAction::make()->label('حذف'),
                ]),
            ]);
    }
}
