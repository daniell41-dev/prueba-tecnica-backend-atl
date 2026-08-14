<?php

declare(strict_types=1);

namespace App\Http;

use App\Domain\Exception\HttpException;

/**
 * Router mínimo: rutas estáticas o con parámetros (`/api/contacts/{id}`),
 * despachadas a un `callable`. Distingue **404** (ninguna ruta coincide con la
 * ruta) de **405** (la ruta existe pero no con ese método, devolviendo el
 * header `Allow` con los métodos válidos).
 */
final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function delete(string $pattern, callable $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[$pattern][$method] = $handler;
    }

    /**
     * @return array{0: callable, 1: array<string, string>}
     * @throws HttpException 404 si ninguna ruta coincide, 405 si coincide con otro método.
     */
    public function match(string $method, string $path): array
    {
        $allowedMethods = [];

        foreach ($this->routes as $pattern => $methods) {
            $params = $this->matchPattern($pattern, $path);
            if ($params === null) {
                continue;
            }

            if (isset($methods[$method])) {
                return [$methods[$method], $params];
            }

            $allowedMethods += $methods;
        }

        if ($allowedMethods !== []) {
            throw HttpException::methodNotAllowed(array_keys($allowedMethods));
        }

        throw HttpException::notFound('La ruta solicitada no existe.');
    }

    /** @return array<string, string>|null `null` si `$path` no coincide con `$pattern`. */
    private function matchPattern(string $pattern, string $path): ?array
    {
        $regex = '#^' . preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';

        if (preg_match($regex, $path, $matches) !== 1) {
            return null;
        }

        return array_filter($matches, static fn (int|string $key): bool => is_string($key), ARRAY_FILTER_USE_KEY);
    }
}
