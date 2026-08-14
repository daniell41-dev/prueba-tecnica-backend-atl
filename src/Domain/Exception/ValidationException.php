<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Los datos enviados no pasan las reglas de validación (bonus 1 y 2). El
 * `Kernel` la traduce a `422` con el detalle de errores por campo.
 */
final class ValidationException extends HttpException
{
    /** @param array<string, string[]> $errors Mensajes de error agrupados por campo. */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Los datos enviados no son válidos.', 422);
    }

    /** @return array<string, string[]> */
    public function errors(): array
    {
        return $this->errors;
    }
}
