<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use RuntimeException;

/**
 * Error genérico con un código HTTP explícito (400 JSON malformado, 404 ruta
 * inexistente, 405 método no permitido...). El `Kernel` la traduce 1:1 a una
 * `JsonResponse` con ese código.
 */
class HttpException extends RuntimeException
{
    /** @param array<string, string> $headers Headers extra para la respuesta (p. ej. `Allow`). */
    public function __construct(
        string $message,
        private readonly int $statusCode,
        private readonly array $headers = [],
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public static function notFound(string $message = 'Recurso no encontrado.'): self
    {
        return new self($message, 404);
    }

    public static function badRequest(string $message = 'La solicitud no es válida.'): self
    {
        return new self($message, 400);
    }

    /** @param string[] $allowedMethods */
    public static function methodNotAllowed(array $allowedMethods): self
    {
        return new self('Método no permitido.', 405, ['Allow' => implode(', ', $allowedMethods)]);
    }
}
