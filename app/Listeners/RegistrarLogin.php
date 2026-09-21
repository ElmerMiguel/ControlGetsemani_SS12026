<?php

namespace App\Listeners;

use App\Services\BitacoraService;
use Illuminate\Auth\Events\Login;

class RegistrarLogin
{
    public function __construct(protected BitacoraService $bitacora) {}

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        $usuario = $event->user;

        $this->bitacora->registrar(
            'auth.login',
            $usuario,
            "Inicio de sesión exitoso para {$usuario->name} ({$usuario->email})",
            null,
            null,
            $usuario->id
        );
    }
}
