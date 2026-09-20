<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificacionController extends Controller
{
    /**
     * Listado paginado de todas las notificaciones del usuario autenticado.
     */
    public function index(Request $request): View
    {
        $notificaciones = $request->user()
            ->notifications()
            ->paginate(15);

        return view('notificaciones.index', compact('notificaciones'));
    }

    /**
     * Marca una notificación específica como leída y redirige a su destino si existe.
     */
    public function leer(Request $request, string $id): RedirectResponse
    {
        $notificacion = $request->user()->notifications()->findOrFail($id);
        $notificacion->markAsRead();

        $url = $notificacion->data['url'] ?? null;

        if ($url) {
            return redirect($url);
        }

        return back()->with('success', 'Notificación marcada como leída.');
    }

    /**
     * Marca todas las notificaciones pendientes del usuario como leídas.
     */
    public function leerTodas(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Todas las notificaciones han sido marcadas como leídas.');
    }
}
