<?php

declare(strict_types=1);

/**
 * Configuración de conexión a base de datos.
 *
 * Por defecto usa SQLite (cero instalación: la extensión viene con PHP y el
 * archivo se crea solo). Cambiar a MySQL es solo variables de entorno — no hay
 * que tocar código: `Infrastructure\Database\Connection` arma el DSN a partir de
 * este arreglo. El schema equivalente vive en `database/migrations/schema.mysql.sql`.
 */
return [
    'driver' => getenv('DB_DRIVER') ?: 'sqlite',

    'sqlite' => [
        // Ruta del archivo .sqlite. Relativa a la raíz del proyecto si no es absoluta.
        'path' => getenv('DB_DATABASE') ?: __DIR__ . '/../database/contacts.sqlite',
    ],

    'mysql' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_DATABASE') ?: 'contacts_api',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset' => 'utf8mb4',
    ],
];
