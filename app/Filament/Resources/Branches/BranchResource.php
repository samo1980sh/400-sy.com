<?php

namespace App\Filament\Resources\Branches;

use App\Filament\Resources\Branches\Pages\ListBranches;
use App\Filament\Resources\Branches\Schemas\BranchForm;
use App\Filament\Resources\Branches\Tables\BranchesTable;
use App\Filament\Resources\RbacResource;
use App\Models\Branch;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BranchResource extends RbacResource
{
    protected static ?string $model = Branch::class;
    protected static ?string $permissionPrefix = 'branches';
    protected static ?string $modelLabel = 'فرع / صالة';
    protected static ?string $pluralModelLabel = 'الأفرع والصالات';
    protected static string|UnitEnum|null $navigationGroup = 'الأفرع والصالات';
    protected static ?string $navigationLabel = 'الأفرع والصالات';
    protected static ?int $navigationSort = 2;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    public static function form(Schema $schema): Schema
    {
        return BranchForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBranches::route('/'),
        ];
    }
}
