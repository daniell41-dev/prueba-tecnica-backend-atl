<?php

declare(strict_types=1);

/**
 * Configuración general de la aplicación.
 *
 * Todo se lee de variables de entorno con un valor por defecto sensato, para no
 * obligar a nadie a crear un `.env` solo para probar la API.
 */
return [
    /** Si es 'true', las respuestas 500 incluyen el mensaje/traza real del error. */
    'debug' => filter_var(getenv('APP_DEBUG') ?: 'true', FILTER_VALIDATE_BOOL),

    /** Orígenes permitidos por CORS. '*' habilita cualquiera (suficiente para el ejercicio). */
    'cors_allowed_origin' => getenv('CORS_ALLOWED_ORIGIN') ?: '*',
];
