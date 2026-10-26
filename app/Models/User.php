<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'username', 'password', 'role_id', 'phone',
        'is_active', 'failed_login_attempts', 'locked_until', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'locked_until' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function hasPermission(string $slug): bool
    {
        $this->loadMissing('role.permissions');

        if (! $this->role) {
            return false;
        }

        if ($this->role->slug === 'super-admin') {
            return true;
        }

        return $this->role->permissions->contains('slug', $slug);
    }

    public function canModule(string $module, string $action = 'view'): bool
    {
        return $this->hasPermission($module.'.'.$action);
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }
}
