<?php

namespace App\Notifications;

use App\Models\CorteCaja;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorteAprobadoNotification extends Notification
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
            ->subject("Corte de caja aprobado: {$caja}")
            ->line("Tu corte de caja para {$caja} ({$inicio} al {$fin}) ha sido aprobado exitosamente por la Administración General.")
            ->line('Saldo final verificado: '.formato_moneda($this->corte->saldo_final))
            ->action('Ver Detalle del Corte', route('cortes.show', $this->corte));
    }

    public function toArray(object $notifiable): array
    {
        $caja = $this->corte->caja?->nombre ?? 'Caja';

        return [
            'corte_id' => $this->corte->id,
            'caja_id' => $this->corte->caja_id,
            'caja_nombre' => $caja,
            'titulo' => 'Corte aprobado',
            'mensaje' => 'Tu corte fue aprobado. Saldo final '.formato_moneda($this->corte->saldo_final),
            'url' => route('cortes.show', $this->corte),
        ];
    }
}
