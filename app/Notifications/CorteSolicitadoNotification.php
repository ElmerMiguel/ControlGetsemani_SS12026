<?php

namespace App\Notifications;

use App\Models\CorteCaja;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorteSolicitadoNotification extends Notification
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
        $solicitante = $this->corte->solicitante?->name ?? 'Tesorero';

        return (new MailMessage)
            ->subject("Nuevo corte de caja solicitado: {$caja}")
            ->line("El usuario {$solicitante} ha solicitado un corte para la caja {$caja}.")
            ->line("Período contable: {$inicio} al {$fin}.")
            ->line('Saldo final preliminar: '.formato_moneda($this->corte->saldo_final))
            ->action('Revisar Corte de Caja', route('cortes.show', $this->corte))
            ->line('Por favor verifique los movimientos antes de aprobar o rechazar.');
    }

    public function toArray(object $notifiable): array
    {
        $inicio = $this->corte->periodo_inicio?->format('d/m/Y');
        $fin = $this->corte->periodo_fin?->format('d/m/Y');
        $caja = $this->corte->caja?->nombre ?? 'Caja';

        return [
            'corte_id' => $this->corte->id,
            'caja_id' => $this->corte->caja_id,
            'caja_nombre' => $caja,
            'titulo' => 'Corte solicitado',
            'mensaje' => "Corte de {$caja} del {$inicio} al {$fin} pendiente de revisión.",
            'url' => route('cortes.show', $this->corte),
        ];
    }
}
