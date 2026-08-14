<?php

declare(strict_types=1);

namespace App\Http;

/**
 * `Response` con el `Content-Type` correcto y el cuerpo serializado a JSON.
 * `$data === null` produce un cuerpo vacío (usado para el `204` de `DELETE`).
 */
final class JsonResponse extends Response
{
    /** @param array<string, string> $headers */
    public function __construct(mixed $data, int $statusCode = 200, array $headers = [])
    {
        $body = $data === null
            ? ''
            : (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($data !== null) {
            $headers = ['Content-Type' => 'application/json; charset=utf-8'] + $headers;
        }

        parent::__construct($statusCode, $body, $headers);
    }
}
