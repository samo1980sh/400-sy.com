<?php

namespace App\Filament\Resources\Permissions;

use App\Filament\Resources\Permissions\Pages\ListPermissions;
use App\Filament\Resources\Permissions\Schemas\PermissionForm;
use App\Filament\Resources\Permissions\Tables\PermissionsTable;
use App\Models\Permission;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PermissionResource extends \App\Filament\Resources\RbacResource
{
    protected static ?string $model = Permission::class;
    protected static ?string $permissionPrefix = 'rbac.permissions';
    protected static ?string $modelLabel = 'صلاحية';
    protected static ?string $pluralModelLabel = 'الصلاحيات';
    protected static string|UnitEnum|null $navigationGroup = 'إدارة الصلاحيات';
    protected static ?string $navigationLabel = 'الصلاحيات';
    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    public static function form(Schema $schema): Schema
    {
        return PermissionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PermissionsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPermissions::route('/'),
        ];
    }
}
