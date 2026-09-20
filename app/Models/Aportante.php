<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aportante extends Model
{
    use Auditable, HasFactory;

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

    /**
     * Accesor para mostrar el CUI enmascarado preservando los últimos 4 dígitos.
     */
    public function getCuiEnmascaradoAttribute(): string
    {
        if (empty($this->cui_dpi)) {
            return '—';
        }

        $cui = preg_replace('/\D/', '', $this->cui_dpi);
        if (strlen($cui) < 4) {
            return $this->cui_dpi;
        }

        $ultimos4 = substr($cui, -4);

        return '•••• •••• • '.$ultimos4;
    }
}
