<?php

namespace App\Exceptions;

use RuntimeException;

class TransicionInvalidaException extends RuntimeException
{
    public function __construct(string $estadoActual, string $estadoNuevo)
    {
        parent::__construct(
            "Transición no permitida: '{$estadoActual}' → '{$estadoNuevo}'."
        );
    }
}
