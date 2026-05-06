<?php

namespace App\Filament\Resources\MeasurementCharts;

use App\Filament\Resources\MeasurementCharts\Pages\ListMeasurementCharts;
use App\Filament\Resources\MeasurementCharts\Schemas\MeasurementChartForm;
use App\Filament\Resources\MeasurementCharts\Tables\MeasurementChartsTable;
use App\Models\MeasurementChart;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MeasurementChartResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = MeasurementChart::class;
    protected static ?string $permissionPrefix = 'measurement-charts';
    protected static ?string $modelLabel = 'صف قياس';
    protected static ?string $pluralModelLabel = 'صفوف القياس';
    protected static string|UnitEnum|null $navigationGroup = 'تهيئة قسم المنتجات';
    protected static ?string $navigationLabel = 'صفوف القياس';
    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    public static function form(Schema $schema): Schema
    {
        return MeasurementChartForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeasurementChartsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeasurementCharts::route('/'),
        ];
    }
}
