<?php

namespace App\Enums;

enum EstadoBien: string
{
    case Correcto = 'correcto';
    case Danado = 'danado';
    case Perdido = 'perdido';

    public function label(): string
    {
        return match ($this) {
            self::Correcto => 'Correcto',
            self::Danado => 'Dañado',
            self::Perdido => 'Perdido',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Correcto => 'success',
            self::Danado => 'warning',
            self::Perdido => 'danger',
        };
    }
}
