<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductDetailsRelationManager extends RelationManager
{
    protected static string $relationship = 'details';
    protected static ?string $title = 'تفاصيل المنتج';
    protected static ?string $modelLabel = 'تفصيل المنتج';
    protected static ?string $pluralModelLabel = 'تفاصيل المنتج';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('label_ar')
                ->label('العنوان بالعربي')
                ->required()
                ->maxLength(255),
            TextInput::make('label_en')
                ->label('العنوان بالانكليزي')
                ->maxLength(255),
            Textarea::make('value_ar')
                ->label('القيمة بالعربي')
                ->required()
                ->rows(3)
                ->columnSpanFull(),
            Textarea::make('value_en')
                ->label('القيمة بالانكليزي')
                ->rows(3)
                ->columnSpanFull(),
            TextInput::make('sort_order')
                ->label('الترتيب')
                ->numeric()
                ->default(0),
            Toggle::make('is_active')
                ->label('فعال')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->badge()
                    ->sortable(),
                TextColumn::make('label_ar')
                    ->label('العنوان بالعربي')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('value_ar')
                    ->label('القيمة بالعربي')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('label_en')
                    ->label('العنوان بالانكليزي')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('إضافة تفصيل'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف'),
                ]),
            ]);
    }
}
