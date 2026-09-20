<?php

namespace App\Exceptions;

use RuntimeException;

class MovimientoInvalidoException extends RuntimeException
{
    public function __construct(string $message = 'El movimiento contable no cumple con las reglas de negocio establecidas.')
    {
        parent::__construct($message);
    }
}
