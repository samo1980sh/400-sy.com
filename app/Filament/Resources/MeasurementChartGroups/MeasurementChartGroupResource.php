<?php

namespace App\Filament\Resources\MeasurementChartGroups;

use App\Filament\Resources\MeasurementChartGroups\Pages\ListMeasurementChartGroups;
use App\Filament\Resources\MeasurementChartGroups\RelationManagers\ChartsRelationManager;
use App\Filament\Resources\MeasurementChartGroups\Schemas\MeasurementChartGroupForm;
use App\Filament\Resources\MeasurementChartGroups\Tables\MeasurementChartGroupsTable;
use App\Models\MeasurementChartGroup;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class MeasurementChartGroupResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = MeasurementChartGroup::class;
    protected static ?string $permissionPrefix = 'measurement-chart-groups';
    protected static ?string $modelLabel = 'مجموعة قياس';
    protected static ?string $pluralModelLabel = 'مجموعات القياس';
    protected static string|UnitEnum|null $navigationGroup = 'تهيئة قسم المنتجات';
    protected static ?string $navigationLabel = 'مجموعات القياس';
    protected static ?int $navigationSort = 6;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    public static function form(Schema $schema): Schema
    {
        return MeasurementChartGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MeasurementChartGroupsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ChartsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeasurementChartGroups::route('/'),
        ];
    }
}
