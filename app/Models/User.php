<?php

namespace App\Models;

use App\Enums\RoleName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Roles que habilitan las capacidades administrativas del usuario.
     *
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Eventos de auditoría ejecutados por el usuario.
     *
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function organizedTournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_organizers')
            ->withPivot(['id', 'assigned_by', 'is_primary', 'assigned_at'])
            ->withTimestamps();
    }

    public function officiatedTournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_officials')
            ->withPivot(['id', 'assigned_by', 'role', 'is_active', 'assigned_at'])
            ->withTimestamps();
    }

    /** @return HasOne<Player, $this> */
    public function player(): HasOne
    {
        return $this->hasOne(Player::class);
    }

    public function notificationPreference(): HasOne
    {
        return $this->hasOne(NotificationPreference::class);
    }

    public function hasRole(RoleName|string $role): bool
    {
        $slug = $role instanceof RoleName ? $role->value : $role;

        return $this->roles->contains('slug', $slug);
    }

    public function isAdministrator(): bool
    {
        return $this->hasRole(RoleName::Administrator);
    }
}
