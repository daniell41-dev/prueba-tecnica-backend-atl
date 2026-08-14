<?php

declare(strict_types=1);

namespace App;

use App\Domain\Exception\HttpException;
use App\Domain\Exception\ValidationException;
use App\Http\JsonResponse;
use App\Http\Middleware\Cors;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Support\Config;
use Throwable;

/**
 * Punto único de despacho: arma la respuesta a partir de un `Request`,
 * atrapa cualquier excepción del dominio y la traduce al código HTTP que le
 * corresponde. `public/index.php` solo construye el `Kernel` y le pasa el
 * request; nada de lógica HTTP vive fuera de esta clase.
 *
 * Recibe el `Request` como parámetro (en vez de leerlo de superglobales), así
 * los tests de feature pueden invocar `handle()` directamente sin levantar un
 * servidor real.
 */
final class Kernel
{
    public function __construct(private readonly Router $router)
    {
    }

    public function handle(Request $request): Response
    {
        if ($request->method === 'OPTIONS') {
            return new Response(204, '', Cors::headers());
        }

        try {
            [$handler, $params] = $this->router->match($request->method, $request->path);
            $request->routeParams = $params;
            $response = $handler($request);
        } catch (ValidationException $exception) {
            $response = new JsonResponse(
                ['message' => $exception->getMessage(), 'errors' => $exception->errors()],
                $exception->statusCode(),
            );
        } catch (HttpException $exception) {
            $response = new JsonResponse(['message' => $exception->getMessage()], $exception->statusCode(), $exception->headers());
        } catch (Throwable $exception) {
            $this->logError($exception);
            $response = new JsonResponse($this->serverErrorPayload($exception), 500);
        }

        return $this->withCors($response);
    }

    private function withCors(Response $response): Response
    {
        return new Response($response->statusCode, $response->body, Cors::headers() + $response->headers);
    }

    /** @return array<string, string> */
    private function serverErrorPayload(Throwable $exception): array
    {
        if (Config::get('app.debug', true)) {
            return ['message' => $exception->getMessage(), 'exception' => $exception::class];
        }

        return ['message' => 'Ocurrió un error inesperado.'];
    }

    private function logError(Throwable $exception): void
    {
        $logFile = dirname(__DIR__) . '/storage/logs/app.log';
        $line = sprintf(
            '[%s] %s: %s in %s:%d%s',
            date(DATE_ATOM),
            $exception::class,
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            PHP_EOL,
        );

        @file_put_contents($logFile, $line, FILE_APPEND);
    }
}
