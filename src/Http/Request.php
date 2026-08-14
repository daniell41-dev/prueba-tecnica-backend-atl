<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Exception\HttpException;
use JsonException;

/**
 * Representa una petición HTTP entrante, desacoplada de las superglobales.
 *
 * `Kernel::handle()` recibe un `Request` en vez de leer `$_SERVER`/`$_GET`
 * directamente, así los tests de feature pueden construir uno a mano y
 * ejercitar la API completa sin levantar un servidor real.
 */
final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, string> $headers
     * @param array<string, string> $routeParams Params de ruta (`{id}`); el Router los rellena al hacer match.
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $headers,
        public readonly string $rawBody,
        public array $routeParams = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $path = rtrim((string) parse_url($uri, PHP_URL_PATH), '/');
        $path = $path === '' ? '/' : $path;

        $query = [];
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

        $headers = function_exists('getallheaders') ? (getallheaders() ?: []) : [];
        $rawBody = (string) file_get_contents('php://input');

        return new self($method, $path, $query, $headers, $rawBody);
    }

    /**
     * Decodifica el body como JSON. Body vacío → `[]` (permite `POST` sin body,
     * que la validación rechazará por campos faltantes con un 422 más útil que
     * un 400 genérico). JSON malformado → `HttpException` 400.
     *
     * @return array<string, mixed>
     */
    public function json(): array
    {
        $trimmed = trim($this->rawBody);
        if ($trimmed === '') {
            return [];
        }

        try {
            $decoded = json_decode($trimmed, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw HttpException::badRequest('El cuerpo de la solicitud no es JSON válido.');
        }

        if (!is_array($decoded)) {
            throw HttpException::badRequest('El cuerpo de la solicitud debe ser un objeto JSON.');
        }

        return $decoded;
    }
}
