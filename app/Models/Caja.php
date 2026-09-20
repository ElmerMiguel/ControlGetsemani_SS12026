<?php

namespace App\Models;

use App\Enums\MedioCaja;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    use Auditable, HasFactory;

    protected $table = 'cajas';

    protected $fillable = [
        'departamento_id',
        'nombre',
        'codigo',
        'medio',
        'saldo_apertura',
        'fecha_apertura',
        'activa',
    ];

    protected function casts(): array
    {
        return [
            'medio' => MedioCaja::class,
            'saldo_apertura' => 'decimal:2',
            'fecha_apertura' => 'date',
            'activa' => 'boolean',
        ];
    }

    public function departamento(): BelongsTo
    {
        return $this->belongsTo(Departamento::class, 'departamento_id');
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'caja_user', 'caja_id', 'user_id');
    }

    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class, 'caja_id');
    }

    public function egresos(): HasMany
    {
        return $this->hasMany(Egreso::class, 'caja_id');
    }

    public function cortesCaja(): HasMany
    {
        return $this->hasMany(CorteCaja::class, 'caja_id');
    }

    public function transferenciasOrigen(): HasMany
    {
        return $this->hasMany(Transferencia::class, 'caja_origen_id');
    }

    public function transferenciasDestino(): HasMany
    {
        return $this->hasMany(Transferencia::class, 'caja_destino_id');
    }
}
