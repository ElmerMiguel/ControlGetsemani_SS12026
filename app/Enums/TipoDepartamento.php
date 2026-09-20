<?php

namespace App\Enums;

enum TipoDepartamento: string
{
    case Consejo = 'consejo';
    case Comite = 'comite';
    case Congregacion = 'congregacion';
    case Junta = 'junta';

    public function label(): string
    {
        return match ($this) {
            self::Consejo => 'Consejo',
            self::Comite => 'Comité',
            self::Congregacion => 'Congregación',
            self::Junta => 'Junta',
        };
    }
}
