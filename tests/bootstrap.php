<?php

declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';
require __DIR__ . '/../vendor/autoload.php';

// La suite usa SQLite en memoria: aislada entre tests (cada `Connection::reset()`
// abre una base en blanco) y nunca toca `database/contacts.sqlite`, el archivo
// real de desarrollo.
putenv('DB_DRIVER=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('APP_DEBUG=true');
