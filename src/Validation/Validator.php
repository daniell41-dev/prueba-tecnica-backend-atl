<?php

declare(strict_types=1);

namespace App\Validation;

use App\Domain\Exception\ValidationException;

/**
 * Bolsa de errores de validación, agrupados por campo (`"phones.0.number" =>
 * [...]` incluido), en el mismo formato que devuelve la API en un `422`.
 *
 * Deliberadamente genérica y sin reglas propias: las reglas del dominio
 * "contacto" viven en `ContactValidator`, que es quien la usa.
 */
final class Validator
{
    /** @var array<string, string[]> */
    private array $errors = [];

    public function addError(string $field, string $message): void
    {
        $this->errors[$field][] = $message;
    }

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string[]> */
    public function errors(): array
    {
        return $this->errors;
    }

    /** @throws ValidationException si se registró algún error. */
    public function throwIfInvalid(): void
    {
        if ($this->hasErrors()) {
            throw new ValidationException($this->errors);
        }
    }
}
