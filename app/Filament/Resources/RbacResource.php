<?php

namespace App\Filament\Resources;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Filament\Resources\Resource;
use Illuminate\Support\Str;

abstract class RbacResource extends Resource
{
    protected static ?string $permissionPrefix = null;

    protected static function permissionPrefix(): string
    {
        if (filled(static::$permissionPrefix)) {
            return static::$permissionPrefix;
        }

        return Str::of(class_basename(static::class))
            ->replace('Resource', '')
            ->snake()
            ->lower()
            ->toString();
    }

    protected static function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    protected static function rbacIsBootstrapped(): bool
    {
        return Role::query()->exists() || Permission::query()->exists();
    }

    protected static function allowsBootstrapFallback(): bool
    {
        return str_starts_with(static::permissionPrefix(), 'rbac.');
    }

    protected static function canAccessAbility(string $ability): bool
    {
        $user = static::currentUser();

        if (! $user || $user->status !== 'active' || $user->user_type !== 'staff') {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if (! static::rbacIsBootstrapped() || static::allowsBootstrapFallback()) {
            return true;
        }

        return $user->hasPermission(static::permissionPrefix() . '.' . $ability);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return static::canAccessAbility('view-any');
    }

    public static function canCreate(): bool
    {
        return static::canAccessAbility('create');
    }

    public static function canEdit($record): bool
    {
        return static::canAccessAbility('update');
    }

    public static function canDelete($record): bool
    {
        return static::canAccessAbility('delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::canAccessAbility('delete');
    }

    public static function canForceDelete($record): bool
    {
        return static::canAccessAbility('force-delete');
    }

    public static function canRestore($record): bool
    {
        return static::canAccessAbility('restore');
    }
}
