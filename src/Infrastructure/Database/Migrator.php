<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use App\Domain\Contact;
use App\Domain\ContactRepositoryInterface;
use App\Domain\Phone;
use App\Domain\PhoneType;
use App\Support\Config;
use App\Support\PhoneNumber;
use PDO;
use RuntimeException;

/**
 * Crea el esquema y, opcionalmente, siembra datos de ejemplo.
 *
 * Se usa desde `bin/migrate.php` (CLI) y desde `public/index.php` en cada
 * request para garantizar que las tablas existen (`CREATE TABLE IF NOT
 * EXISTS`, idempotente y barato) — la API nunca revienta por una tabla ausente
 * aunque nadie haya corrido la migración a mano primero.
 */
final class Migrator
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ContactRepositoryInterface $repository,
    ) {
    }

    public function migrate(): void
    {
        $driver = Config::get('database.driver', 'sqlite');
        $schemaFile = dirname(__DIR__, 3) . "/database/migrations/schema.{$driver}.sql";

        if (!is_file($schemaFile)) {
            throw new RuntimeException("No existe el schema para el driver \"{$driver}\" ({$schemaFile}).");
        }

        $this->pdo->exec((string) file_get_contents($schemaFile));
    }

    /** Elimina las tablas y vuelve a crearlas vacías. */
    public function fresh(): void
    {
        $this->pdo->exec('DROP TABLE IF EXISTS phones');
        $this->pdo->exec('DROP TABLE IF EXISTS contacts');
        $this->migrate();
    }

    /** Carga `database/seeds/contacts.json` si las tablas están vacías. */
    public function seed(): void
    {
        $seedFile = dirname(__DIR__, 3) . '/database/seeds/contacts.json';
        $data = json_decode((string) file_get_contents($seedFile), true, flags: JSON_THROW_ON_ERROR);

        foreach ($data['contacts'] as $row) {
            $phones = array_map(
                static fn (array $phone): Phone => Phone::create(
                    PhoneType::from($phone['type']),
                    PhoneNumber::normalize($phone['number']),
                ),
                $row['phones'] ?? [],
            );

            $contact = Contact::createNew(
                firstName: $row['first_name'],
                lastName: $row['last_name'],
                email: $row['email'],
                company: $row['company'] ?? null,
                favorite: $row['favorite'] ?? false,
                phones: $phones,
            );

            if (!$this->repository->emailExists($contact->email)) {
                $this->repository->create($contact);
            }
        }
    }
}
