<?php

declare(strict_types=1);

/**
 * Autoloader PSR-4 propio para el namespace `App\`.
 *
 * El ejercicio pide una API "sin framework"; para que eso sea honesto de verdad,
 * la API tampoco depende de Composer para arrancar: `public/index.php` solo
 * necesita este archivo. `composer.json` declara el mismo mapeo PSR-4 (para que
 * el autocompletado del IDE y el autoloader de PHPUnit en los tests funcionen),
 * pero `composer install` es opcional para correr la API — solo hace falta para
 * ejecutar la suite de tests.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($path)) {
        require $path;
    }
});
