<?php

namespace App\Filament\Resources\MeasurementChartGroups\Schemas;

use App\Models\MeasurementChartGroup;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class MeasurementChartGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(static::components());
    }

    public static function components(?MeasurementChartGroup $record = null): array
    {
        return [
            Grid::make()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('اسم المجموعة')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    FileUpload::make('guide_image')
                        ->label('صورة توضيحية لطريقة القياس')
                        ->helperText('هذه الصورة تظهر مرة واحدة بجانب جدول القياسات الخاص بالمجموعة.')
                        ->disk('public')
                        ->directory('measurement-charts/guides')
                        ->visibility('public')
                        ->deleteUploadedFileUsing(fn (string $file): bool => Storage::disk('public')->delete($file))
                        ->image()
                        ->imageEditor()
                        ->maxSize(2048)
                        ->columnSpanFull(),
                ]),
        ];
    }
}
