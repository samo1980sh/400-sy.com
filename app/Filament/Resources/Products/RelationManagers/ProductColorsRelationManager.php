<?php

namespace App\Filament\Resources\Products\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductColorsRelationManager extends RelationManager
{
    protected static string $relationship = 'productColors';
    protected static ?string $title = 'ألوان المنتج';
    protected static ?string $modelLabel = 'لون المنتج';
    protected static ?string $pluralModelLabel = 'ألوان المنتج';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('color_code')
                ->label('رمز اللون')
                ->required()
                ->maxLength(50)
                ->helperText('الرمز خاص بهذا المنتج، وقد يتكرر في منتجات أخرى بمعنى مختلف.'),
            TextInput::make('color_name_ar')
                ->label('اسم اللون بالعربي')
                ->required()
                ->maxLength(255),
            TextInput::make('color_name_en')
                ->label('اسم اللون بالانكليزي')
                ->maxLength(255),
            TextInput::make('color_hex')
                ->label('HEX')
                ->maxLength(20)
                ->placeholder('#000000')
                ->helperText('اختياري، يستخدم لعرض اللون بصرياً إن توفر.')
                ->extraInputAttributes(['dir' => 'ltr']),
            TextInput::make('sort_order')
                ->label('الترتيب')
                ->numeric()
                ->default(0),
            Select::make('status')
                ->label('الحالة')
                ->options([
                    'active' => 'فعال',
                    'inactive' => 'غير فعال',
                ])
                ->default('active')
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('color_code')
                    ->label('رمز اللون')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('color_name_ar')
                    ->label('اللون بالعربي')
                    ->searchable(),
                TextColumn::make('color_name_en')
                    ->label('اللون بالانكليزي')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('color_hex')
                    ->label('HEX')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('sort_order')
                    ->label('الترتيب')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'active' => 'فعال',
                        'inactive' => 'غير فعال',
                        default => (string) $state,
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('إضافة لون'),
            ])
            ->recordActions([
                EditAction::make()->label('تعديل'),
                DeleteAction::make()->label('حذف'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('حذف'),
                ]),
            ]);
    }
}
