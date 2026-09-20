<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bitacora extends Model
{
    use HasFactory;

    protected $table = 'bitacora_auditoria';

    public const CREATED_AT = 'fecha_hora';

    public const UPDATED_AT = null;

    protected $fillable = [
        'usuario_id',
        'accion',
        'tabla_afectada',
        'registro_id',
        'descripcion',
        'datos_antes',
        'datos_despues',
        'ip',
        'user_agent',
        'fecha_hora',
    ];

    protected function casts(): array
    {
        return [
            'datos_antes' => 'array',
            'datos_despues' => 'array',
            'fecha_hora' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
