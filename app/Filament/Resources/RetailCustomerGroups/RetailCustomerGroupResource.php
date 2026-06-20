<?php

namespace App\Filament\Resources\RetailCustomerGroups;

use App\Filament\Resources\RetailCustomerGroups\Pages\ListRetailCustomerGroups;
use App\Filament\Resources\RetailCustomerGroups\Schemas\RetailCustomerGroupForm;
use App\Filament\Resources\RetailCustomerGroups\Tables\RetailCustomerGroupsTable;
use App\Models\RetailCustomerGroup;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RetailCustomerGroupResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = RetailCustomerGroup::class;
    protected static ?string $permissionPrefix = 'customer-groups.retail';
    protected static ?string $modelLabel = 'فئة مفرق';
    protected static ?string $pluralModelLabel = 'فئات المفرق';
    protected static string|UnitEnum|null $navigationGroup = 'الزبائن والحسابات';
    protected static ?string $navigationLabel = 'فئات المفرق';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function form(Schema $schema): Schema
    {
        return RetailCustomerGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RetailCustomerGroupsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRetailCustomerGroups::route('/'),
        ];
    }
}
