<?php

namespace App\Filament\Resources\WarehouseUsers;

use App\Filament\Resources\WarehouseUsers\Pages\ListWarehouseUsers;
use App\Filament\Resources\WarehouseUsers\Schemas\WarehouseUserForm;
use App\Filament\Resources\WarehouseUsers\Tables\WarehouseUsersTable;
use App\Models\WarehouseUser;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WarehouseUserResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = WarehouseUser::class;
    protected static ?string $permissionPrefix = 'warehouse-users';
    protected static ?string $modelLabel = 'مستخدم مستودع';
    protected static ?string $pluralModelLabel = 'مستخدمو المستودع';
    protected static string|UnitEnum|null $navigationGroup = 'المستودعات';
    protected static ?string $navigationLabel = 'مستخدمو المستودعات';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function form(Schema $schema): Schema
    {
        return WarehouseUserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WarehouseUsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWarehouseUsers::route('/'),
        ];
    }
}
