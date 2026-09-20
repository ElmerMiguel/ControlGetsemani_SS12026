<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Estado de la Auditoría
    |--------------------------------------------------------------------------
    |
    | Define si el registro automático de bitácora está activo o inactivo.
    | Útil para desactivarlo en procesos masivos, seeders o pruebas específicas.
    |
    */

    'activa' => env('AUDITORIA_ACTIVA', true),

    /*
    |--------------------------------------------------------------------------
    | Campos Excluidos de Auditoría
    |--------------------------------------------------------------------------
    |
    | Lista de atributos que NUNCA deben ser registrados en datos_antes ni
    | datos_despues por motivos de seguridad y redundancia (RN-16).
    |
    */

    'campos_excluidos' => [
        'password',
        'remember_token',
        'created_at',
        'updated_at',
        'deleted_at',
    ],

];
