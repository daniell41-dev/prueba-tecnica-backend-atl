#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * CLI de migraciones.
 *
 *   php bin/migrate.php              crea las tablas si no existen
 *   php bin/migrate.php --fresh      elimina y vuelve a crear las tablas (borra los datos)
 *   php bin/migrate.php --seed       además, carga database/seeds/contacts.json
 *   php bin/migrate.php --fresh --seed
 */

require __DIR__ . '/../src/autoload.php';

use App\Infrastructure\Database\Connection;
use App\Infrastructure\Database\Migrator;
use App\Infrastructure\Persistence\PdoContactRepository;

$options = getopt('', ['fresh', 'seed']);

$pdo = Connection::make();
$migrator = new Migrator($pdo, new PdoContactRepository($pdo));

if (array_key_exists('fresh', $options)) {
    $migrator->fresh();
    echo "Tablas recreadas.\n";
} else {
    $migrator->migrate();
    echo "Tablas verificadas/creadas.\n";
}

if (array_key_exists('seed', $options)) {
    $migrator->seed();
    echo "Datos de ejemplo cargados.\n";
}
