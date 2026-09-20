<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'activo',
        'must_change_password',
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
            'activo' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function cajas(): BelongsToMany
    {
        return $this->belongsToMany(Caja::class, 'caja_user', 'user_id', 'caja_id');
    }

    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class, 'usuario_id');
    }

    public function egresos(): HasMany
    {
        return $this->hasMany(Egreso::class, 'usuario_id');
    }

    public function transferencias(): HasMany
    {
        return $this->hasMany(Transferencia::class, 'usuario_id');
    }

    public function cortesSolicitados(): HasMany
    {
        return $this->hasMany(CorteCaja::class, 'solicitado_por');
    }

    public function cortesRevisados(): HasMany
    {
        return $this->hasMany(CorteCaja::class, 'revisado_por');
    }

    public function bitacoras(): HasMany
    {
        return $this->hasMany(Bitacora::class, 'usuario_id');
    }
}
