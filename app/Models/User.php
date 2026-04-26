<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The users table in this project does not have updated_at / deleted_at.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'avatar',
        'status',
        'user_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === 'active' && ($this->user_type === 'staff' || $this->hasRole('super-admin'));
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = array_map(static fn (string $role): string => strtolower(trim($role)), (array) $roles);

        return $this->roles()
            ->where(function ($query) use ($roles): void {
                $query->whereIn('slug', $roles)
                    ->orWhereIn('name', $roles);
            })
            ->exists();
    }

    public function allPermissions(): \Illuminate\Support\Collection
    {
        return $this->roles()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->unique('id')
            ->values();
    }

    public function hasPermission(string|array $permissions): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        $permissions = array_map(static fn (string $permission): string => strtolower(trim($permission)), (array) $permissions);

        return $this->allPermissions()
            ->pluck('slug')
            ->map(static fn (string $permission): string => strtolower($permission))
            ->intersect($permissions)
            ->isNotEmpty();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }
}
