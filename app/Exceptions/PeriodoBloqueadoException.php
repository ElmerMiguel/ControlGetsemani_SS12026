<?php

namespace App\Exceptions;

use RuntimeException;

class PeriodoBloqueadoException extends RuntimeException
{
    public function __construct(string $message = 'La fecha del movimiento corresponde a un periodo bloqueado por corte de caja.')
    {
        parent::__construct($message);
    }
}
