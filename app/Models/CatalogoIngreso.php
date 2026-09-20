<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogoIngreso extends Model
{
    use Auditable, HasFactory;

    protected $table = 'catalogo_ingresos';

    protected $fillable = [
        'codigo',
        'nombre',
        'es_transferencia',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'es_transferencia' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class, 'cuenta_ingreso_id');
    }
}
