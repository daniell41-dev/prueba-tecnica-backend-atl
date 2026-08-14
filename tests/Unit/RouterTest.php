<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Exception\HttpException;
use App\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function test_resuelve_una_ruta_estatica(): void
    {
        $router = new Router();
        $router->get('/api/contacts', fn () => 'index');

        [$handler, $params] = $router->match('GET', '/api/contacts');

        $this->assertSame('index', $handler());
        $this->assertSame([], $params);
    }

    public function test_resuelve_parametros_de_ruta(): void
    {
        $router = new Router();
        $router->get('/api/contacts/{id}', fn () => 'show');

        [, $params] = $router->match('GET', '/api/contacts/abc-123');

        $this->assertSame(['id' => 'abc-123'], $params);
    }

    public function test_lanza_404_si_ninguna_ruta_coincide(): void
    {
        $router = new Router();
        $router->get('/api/contacts', fn () => null);

        try {
            $router->match('GET', '/no-existe');
            $this->fail('Se esperaba una HttpException.');
        } catch (HttpException $exception) {
            $this->assertSame(404, $exception->statusCode());
        }
    }

    public function test_lanza_405_con_el_header_allow_si_la_ruta_existe_con_otro_metodo(): void
    {
        $router = new Router();
        $router->get('/api/contacts', fn () => null);
        $router->post('/api/contacts', fn () => null);

        try {
            $router->match('DELETE', '/api/contacts');
            $this->fail('Se esperaba una HttpException.');
        } catch (HttpException $exception) {
            $this->assertSame(405, $exception->statusCode());
            $this->assertSame('GET, POST', $exception->headers()['Allow']);
        }
    }
}
