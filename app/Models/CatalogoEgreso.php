<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogoEgreso extends Model
{
    use Auditable, HasFactory;

    protected $table = 'catalogo_egresos';

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

    public function egresos(): HasMany
    {
        return $this->hasMany(Egreso::class, 'cuenta_egreso_id');
    }
}
