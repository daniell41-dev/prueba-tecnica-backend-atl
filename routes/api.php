<?php

declare(strict_types=1);

use App\Http\Controllers\ContactController;
use App\Http\Router;

/**
 * Definición de rutas de la API.
 *
 * Se incluye desde `public/index.php`, que ya construyó el `Router` y el
 * `ContactController` con sus dependencias — este archivo solo declara el
 * mapa ruta → acción, sin instanciar nada.
 */
return static function (Router $router, ContactController $controller): void {
    $router->get('/api/contacts', [$controller, 'index']);
    $router->post('/api/contacts', [$controller, 'store']);
    $router->get('/api/contacts/{id}', [$controller, 'show']);
    $router->delete('/api/contacts/{id}', [$controller, 'destroy']);
};
