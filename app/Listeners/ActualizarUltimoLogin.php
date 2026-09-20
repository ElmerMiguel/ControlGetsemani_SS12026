<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;

class ActualizarUltimoLogin
{
    /**
     * Registra la fecha y hora del último acceso exitoso del usuario.
     */
    public function handle(Login $event): void
    {
        $event->user->forceFill([
            'last_login_at' => now(),
        ])->save();
    }
}
