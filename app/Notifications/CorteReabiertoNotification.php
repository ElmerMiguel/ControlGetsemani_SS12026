<?php

namespace App\Notifications;

use App\Models\CorteCaja;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorteReabiertoNotification extends Notification
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
            ->subject("Corte de caja reabierto: {$caja}")
            ->line("El corte de caja para {$caja} ({$inicio} al {$fin}) ha sido reabierto por el Administrador General.")
            ->line("Motivo de reapertura: {$this->corte->observaciones}")
            ->line('El período ha sido desbloqueado. Puedes editar o ingresar nuevos movimientos contables según corresponda.')
            ->action('Ver Corte Reabierto', route('cortes.show', $this->corte));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'corte_id' => $this->corte->id,
            'caja_id' => $this->corte->caja_id,
            'caja_nombre' => $this->corte->caja?->nombre ?? 'Caja',
            'titulo' => 'Corte reabierto',
            'mensaje' => "Tu corte fue reabierto. Motivo: {$this->corte->observaciones}",
            'url' => route('cortes.show', $this->corte),
        ];
    }
}
