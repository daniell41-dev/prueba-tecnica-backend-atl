<?php

declare(strict_types=1);

/**
 * Front controller — único punto de entrada de la API.
 *
 *   php -S localhost:8000 -t public public/index.php
 *
 * Arma las dependencias a mano (sin contenedor de DI: para el tamaño de este
 * ejercicio sería ceremonia sin beneficio — YAGNI) y delega todo lo demás al
 * `Kernel`.
 */

require __DIR__ . '/../src/autoload.php';

use App\Application\ContactService;
use App\Http\Controllers\ContactController;
use App\Http\Request;
use App\Http\Router;
use App\Infrastructure\Database\Connection;
use App\Infrastructure\Database\Migrator;
use App\Infrastructure\Persistence\PdoContactRepository;
use App\Kernel;
use App\Validation\ContactValidator;

$pdo = Connection::make();
$repository = new PdoContactRepository($pdo);

// Idempotente y barato: garantiza que las tablas existen aunque nadie haya
// corrido `bin/migrate.php` a mano antes del primer request.
(new Migrator($pdo, $repository))->migrate();

$service = new ContactService($repository, new ContactValidator($repository));
$controller = new ContactController($service);

$router = new Router();
(require __DIR__ . '/../routes/api.php')($router, $controller);

(new Kernel($router))->handle(Request::fromGlobals())->send();
