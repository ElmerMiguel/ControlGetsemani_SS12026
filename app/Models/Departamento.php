<?php

namespace App\Models;

use App\Enums\TipoDepartamento;
use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Departamento extends Model
{
    use Auditable, HasFactory;

    protected $table = 'departamentos';

    protected $fillable = [
        'nombre',
        'tipo',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoDepartamento::class,
            'activo' => 'boolean',
        ];
    }

    public function cajas(): HasMany
    {
        return $this->hasMany(Caja::class, 'departamento_id');
    }
}
