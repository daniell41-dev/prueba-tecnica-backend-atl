<?php

declare(strict_types=1);

namespace App\Http;

/**
 * Respuesta HTTP genérica: código, cuerpo ya serializado y headers.
 *
 * Se construye en memoria y solo toca la salida real en `send()`, para que
 * los tests puedan inspeccionar `statusCode`/`body` sin capturar buffers de
 * salida.
 */
class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        public readonly int $statusCode,
        public readonly string $body = '',
        public readonly array $headers = [],
    ) {
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }
        echo $this->body;
    }
}
