<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Application\ContactService;
use App\Http\Controllers\ContactController;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Database\Migrator;
use App\Infrastructure\Persistence\PdoContactRepository;
use App\Kernel;
use App\Validation\ContactValidator;
use PHPUnit\Framework\TestCase;

/**
 * Ejercita la API completa (Kernel → Router → Controller → Service →
 * Validator → PdoContactRepository, contra SQLite en memoria) tal cual la
 * arma `public/index.php`, pero pasando un `Request` construido a mano en
 * vez de levantar un servidor HTTP real.
 */
final class ContactApiTest extends TestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        Connection::reset();
        $pdo = Connection::make();
        $repository = new PdoContactRepository($pdo);
        (new Migrator($pdo, $repository))->fresh();

        $service = new ContactService($repository, new ContactValidator($repository));
        $controller = new ContactController($service);

        $router = new Router();
        (require dirname(__DIR__, 2) . '/routes/api.php')($router, $controller);

        $this->kernel = new Kernel($router);
    }

    public function test_lista_vacia_al_inicio(): void
    {
        $response = $this->request('GET', '/api/contacts');

        $this->assertSame(200, $response->statusCode);
        $this->assertSame(['contacts' => []], $this->decode($response));
    }

    public function test_crea_lista_consulta_y_elimina_un_contacto(): void
    {
        $create = $this->request('POST', '/api/contacts', [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => 'ada@example.com',
            'phones' => [
                ['type' => 'mobile', 'number' => '+52 55 1111 2222'],
                ['type' => 'work', 'number' => '5533334444'],
            ],
        ]);

        $this->assertSame(201, $create->statusCode);
        $created = $this->decode($create)['contact'];
        $this->assertSame('5511112222', $created['phones'][0]['number']);
        $this->assertSame("/api/contacts/{$created['id']}", $create->headers['Location']);

        $list = $this->request('GET', '/api/contacts');
        $this->assertCount(1, $this->decode($list)['contacts']);

        $show = $this->request('GET', "/api/contacts/{$created['id']}");
        $this->assertSame(200, $show->statusCode);
        $this->assertSame($created['id'], $this->decode($show)['contact']['id']);

        $delete = $this->request('DELETE', "/api/contacts/{$created['id']}");
        $this->assertSame(204, $delete->statusCode);
        $this->assertSame('', $delete->body);

        $afterDelete = $this->request('GET', '/api/contacts');
        $this->assertSame([], $this->decode($afterDelete)['contacts']);
    }

    public function test_devuelve_422_con_datos_vacios(): void
    {
        $response = $this->request('POST', '/api/contacts', ['first_name' => '', 'last_name' => '', 'email' => '']);

        $this->assertSame(422, $response->statusCode);
        $this->assertArrayHasKey('first_name', $this->decode($response)['errors']);
    }

    public function test_devuelve_422_si_el_email_ya_esta_registrado(): void
    {
        $payload = ['first_name' => 'Ada', 'last_name' => 'Lovelace', 'email' => 'ada@example.com'];
        $this->request('POST', '/api/contacts', $payload);

        $second = $this->request('POST', '/api/contacts', $payload);

        $this->assertSame(422, $second->statusCode);
        $this->assertArrayHasKey('email', $this->decode($second)['errors']);
    }

    public function test_devuelve_400_con_json_malformado(): void
    {
        $response = $this->kernel->handle(new Request('POST', '/api/contacts', [], [], '{esto no es json'));

        $this->assertSame(400, $response->statusCode);
    }

    public function test_devuelve_404_al_consultar_un_contacto_inexistente(): void
    {
        $response = $this->request('GET', '/api/contacts/no-existe');

        $this->assertSame(404, $response->statusCode);
    }

    public function test_devuelve_404_al_eliminar_un_contacto_inexistente(): void
    {
        $response = $this->request('DELETE', '/api/contacts/no-existe');

        $this->assertSame(404, $response->statusCode);
    }

    public function test_devuelve_405_con_header_allow_en_metodo_no_permitido(): void
    {
        $response = $this->request('PUT', '/api/contacts');

        $this->assertSame(405, $response->statusCode);
        $this->assertArrayHasKey('Allow', $response->headers);
    }

    public function test_responde_cors_en_preflight_options(): void
    {
        $response = $this->request('OPTIONS', '/api/contacts');

        $this->assertSame(204, $response->statusCode);
        $this->assertArrayHasKey('Access-Control-Allow-Origin', $response->headers);
    }

    /** @param array<string, mixed>|null $body */
    private function request(string $method, string $path, ?array $body = null): Response
    {
        $rawBody = $body === null ? '' : (string) json_encode($body);

        return $this->kernel->handle(new Request($method, $path, [], [], $rawBody));
    }

    /** @return array<string, mixed> */
    private function decode(Response $response): array
    {
        return json_decode($response->body, true);
    }
}
