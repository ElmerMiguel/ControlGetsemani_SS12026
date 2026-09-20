<?php

namespace App\Listeners;

use App\Services\BitacoraService;
use Illuminate\Auth\Events\Logout;

class RegistrarLogout
{
    public function __construct(protected BitacoraService $bitacora) {}

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        $usuario = $event->user;

        if ($usuario) {
            $this->bitacora->registrar(
                'auth.logout',
                $usuario,
                "Cierre de sesión para {$usuario->name} ({$usuario->email})",
                null,
                null,
                $usuario->id
            );
        }
    }
}
