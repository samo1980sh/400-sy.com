<?php

namespace App\Filament\Resources\JobVacancies\RelationManagers\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;

class JobApplicationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(): array
    {
        return [
            Grid::make()
                ->columns(2)
                ->schema([
                    Select::make('status')
                        ->label('الحالة')
                        ->options([
                            'new' => 'جديد',
                            'reviewing' => 'قيد المراجعة',
                            'accepted' => 'مقبول',
                            'rejected' => 'مرفوض',
                        ])
                        ->required(),
                    RichEditor::make('notes')
                        ->label('ملاحظات داخلية')
                        ->columnSpanFull(),
                ]),
        ];
    }
}
