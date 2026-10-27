<?php

namespace App\Notifications;

use App\Models\ComprobanteDonacion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ComprobanteDonacionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ComprobanteDonacion $comprobante
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $aportanteNombre = $this->comprobante->aportante?->nombre_completo ?? 'Apreciable hermano(a)';
        $monto = formato_moneda($this->comprobante->ingreso?->monto ?? 0);
        $numero = $this->comprobante->numero;

        $mail = (new MailMessage)
            ->subject("Comprobante de Donación {$numero} - Iglesia Getsemaní")
            ->greeting("Estimado(a) {$aportanteNombre},")
            ->line("Le agradecemos sinceramente su ofrenda/aportación a la obra del Señor en la Iglesia Getsemaní por un valor de {$monto}.")
            ->line("Adjunto a este correo encontrará su comprobante formal de donación con número {$numero}.")
            ->line('Que Dios bendiga abundantemente su fidelidad y generosidad.')
            ->salutation('Atentamente, Administración y Tesorería - Iglesia Getsemaní');

        if ($this->comprobante->ruta_pdf && Storage::disk('local')->exists($this->comprobante->ruta_pdf)) {
            $mail->attach(Storage::disk('local')->path($this->comprobante->ruta_pdf), [
                'as' => "{$numero}.pdf",
                'mime' => 'application/pdf',
            ]);
        }

        return $mail;
    }
}
