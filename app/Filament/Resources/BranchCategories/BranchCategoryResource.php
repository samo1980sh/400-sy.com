<?php

namespace App\Filament\Resources\BranchCategories;

use App\Filament\Resources\BranchCategories\Pages\ListBranchCategories;
use App\Filament\Resources\BranchCategories\Schemas\BranchCategoryForm;
use App\Filament\Resources\BranchCategories\Tables\BranchCategoriesTable;
use App\Filament\Resources\RbacResource;
use App\Models\BranchCategory;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class BranchCategoryResource extends RbacResource
{
    protected static ?string $model = BranchCategory::class;
    protected static ?string $permissionPrefix = 'branch-categories';
    protected static ?string $modelLabel = 'تصنيف';
    protected static ?string $pluralModelLabel = 'التصنيفات';
    protected static string|UnitEnum|null $navigationGroup = 'الأفرع والصالات';
    protected static ?string $navigationLabel = 'التصنيفات';
    protected static ?int $navigationSort = 1;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;

    public static function form(Schema $schema): Schema
    {
        return BranchCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BranchCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBranchCategories::route('/'),
        ];
    }
}
