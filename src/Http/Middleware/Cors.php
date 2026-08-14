<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Config;

/**
 * Headers CORS, aplicados a toda respuesta (incluidas las de error) para que
 * un frontend en otro origen —como la app Angular del ejercicio hermano—
 * pueda llamar a esta API desde el navegador.
 */
final class Cors
{
    /** @return array<string, string> */
    public static function headers(): array
    {
        return [
            'Access-Control-Allow-Origin' => (string) Config::get('app.cors_allowed_origin', '*'),
            'Access-Control-Allow-Methods' => 'GET, POST, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept',
        ];
    }
}
