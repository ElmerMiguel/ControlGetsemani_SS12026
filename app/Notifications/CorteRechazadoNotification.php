<?php

namespace App\Notifications;

use App\Models\CorteCaja;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorteRechazadoNotification extends Notification
{
    use Queueable;

    public function __construct(public CorteCaja $corte)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $inicio = $this->corte->periodo_inicio?->format('d/m/Y');
        $fin = $this->corte->periodo_fin?->format('d/m/Y');
        $caja = $this->corte->caja?->nombre ?? 'Caja';

        return (new MailMessage)
            ->subject("Corte de caja rechazado: {$caja}")
            ->line("Tu solicitud de corte para {$caja} ({$inicio} al {$fin}) ha sido rechazada.")
            ->line("Observación de Administración: {$this->corte->observaciones}")
            ->line('El período ha sido desbloqueado para que puedas corregir los movimientos necesarios y solicitar un nuevo corte.')
            ->action('Ver Corte Rechazado', route('cortes.show', $this->corte));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'corte_id' => $this->corte->id,
            'caja_id' => $this->corte->caja_id,
            'caja_nombre' => $this->corte->caja?->nombre ?? 'Caja',
            'titulo' => 'Corte rechazado',
            'mensaje' => "Tu corte fue rechazado. Observación: {$this->corte->observaciones}",
            'url' => route('cortes.show', $this->corte),
        ];
    }
}
