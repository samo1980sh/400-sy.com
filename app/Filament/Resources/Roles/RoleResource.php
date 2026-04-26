<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Schemas\RoleForm;
use App\Filament\Resources\Roles\Tables\RolesTable;
use App\Models\Role;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RoleResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = Role::class;
    protected static ?string $permissionPrefix = 'rbac.roles';
    protected static ?string $modelLabel = 'دور';
    protected static ?string $pluralModelLabel = 'الأدوار';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة الصلاحيات';
    protected static ?string $navigationLabel = 'الأدوار';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
        ];
    }
}
