<?php

namespace App\Enums;

enum EstadoCorte: string
{
    case Pendiente = 'pendiente';
    case Aprobado = 'aprobado';
    case Rechazado = 'rechazado';
    case Reabierto = 'reabierto';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Aprobado => 'Aprobado',
            self::Rechazado => 'Rechazado',
            self::Reabierto => 'Reabierto',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pendiente => 'sky',
            self::Aprobado => 'success',
            self::Rechazado, self::Reabierto => 'danger',
        };
    }
}
