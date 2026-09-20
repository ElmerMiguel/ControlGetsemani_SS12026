<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aportante extends Model
{
    use HasFactory;

    protected $table = 'aportantes';

    protected $fillable = [
        'nombre_completo',
        'cui_dpi',
        'telefono',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function ingresos(): HasMany
    {
        return $this->hasMany(Ingreso::class, 'aportante_id');
    }
}
