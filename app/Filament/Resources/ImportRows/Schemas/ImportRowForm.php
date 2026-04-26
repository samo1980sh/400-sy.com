<?php

namespace App\Filament\Resources\ImportRows\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ImportRowForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('batch_id')
                    ->required()
                    ->numeric(),
                TextInput::make('row_number')
                    ->required()
                    ->numeric(),
                Textarea::make('raw_payload')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'valid' => 'Valid',
                        'invalid' => 'Invalid',
                        'applied' => 'Applied',
                        'skipped' => 'Skipped',
                    ])
                    ->default('pending')
                    ->required(),
                Textarea::make('error_message')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
