<?php

namespace App\Enums;

enum MedioCaja: string
{
    case Efectivo = 'efectivo';
    case Banco = 'banco';

    public function label(): string
    {
        return match ($this) {
            self::Efectivo => 'Efectivo',
            self::Banco => 'Banco',
        };
    }
}
