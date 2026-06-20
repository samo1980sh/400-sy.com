<?php

namespace App\Filament\Resources\WholesaleCustomerGroups;

use App\Filament\Resources\WholesaleCustomerGroups\Pages\ListWholesaleCustomerGroups;
use App\Filament\Resources\WholesaleCustomerGroups\Schemas\WholesaleCustomerGroupForm;
use App\Filament\Resources\WholesaleCustomerGroups\Tables\WholesaleCustomerGroupsTable;
use App\Models\WholesaleCustomerGroup;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WholesaleCustomerGroupResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = WholesaleCustomerGroup::class;
    protected static ?string $permissionPrefix = 'customer-groups.wholesale';
    protected static ?string $modelLabel = 'فئة تاجر';
    protected static ?string $pluralModelLabel = 'فئات التاجر';
    protected static string|UnitEnum|null $navigationGroup = 'التجار والجملة';
    protected static ?string $navigationLabel = 'فئات التاجر';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static function form(Schema $schema): Schema
    {
        return WholesaleCustomerGroupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WholesaleCustomerGroupsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWholesaleCustomerGroups::route('/'),
        ];
    }
}
