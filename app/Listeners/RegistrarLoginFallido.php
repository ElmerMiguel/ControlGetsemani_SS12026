<?php

namespace App\Listeners;

use App\Services\BitacoraService;
use Illuminate\Auth\Events\Failed;

class RegistrarLoginFallido
{
    public function __construct(protected BitacoraService $bitacora) {}

    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        $email = $event->credentials['email'] ?? 'desconocido';
        $usuarioId = $event->user ? $event->user->id : null;

        $this->bitacora->registrar(
            'auth.failed',
            $event->user,
            "Intento fallido de inicio de sesión con correo: {$email}",
            null,
            null,
            $usuarioId
        );
    }
}
