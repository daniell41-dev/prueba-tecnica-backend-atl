<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Contact;
use App\Domain\ContactRepositoryInterface;
use App\Domain\Phone;
use App\Domain\PhoneType;
use DateTimeImmutable;
use PDO;

/**
 * Implementación de `ContactRepositoryInterface` sobre PDO.
 *
 * SQL estándar (sin funciones específicas de un motor) para que el mismo
 * código sirva tanto con el driver `sqlite` como con `mysql`
 * (`config/database.php`).
 */
final class PdoContactRepository implements ContactRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function all(): array
    {
        $statement = $this->pdo->query('SELECT * FROM contacts ORDER BY first_name, last_name');
        $rows = $statement->fetchAll();

        if ($rows === []) {
            return [];
        }

        $phonesByContact = $this->phonesForContacts(array_column($rows, 'id'));

        return array_map(
            fn (array $row): Contact => $this->hydrate($row, $phonesByContact[$row['id']] ?? []),
            $rows,
        );
    }

    public function find(string $id): ?Contact
    {
        $statement = $this->pdo->prepare('SELECT * FROM contacts WHERE id = :id');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row, $this->phonesForContacts([$id])[$id] ?? []);
    }

    public function emailExists(string $email, ?string $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM contacts WHERE LOWER(email) = LOWER(:email)';
        $params = ['email' => $email];

        if ($excludeId !== null) {
            $sql .= ' AND id != :excludeId';
            $params['excludeId'] = $excludeId;
        }

        $statement = $this->pdo->prepare($sql . ' LIMIT 1');
        $statement->execute($params);

        return $statement->fetchColumn() !== false;
    }

    public function create(Contact $contact): void
    {
        $this->pdo->beginTransaction();

        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO contacts (id, first_name, last_name, email, company, favorite, created_at, updated_at)
                 VALUES (:id, :first_name, :last_name, :email, :company, :favorite, :created_at, :updated_at)',
            );
            $statement->execute([
                'id' => $contact->id,
                'first_name' => $contact->firstName,
                'last_name' => $contact->lastName,
                'email' => $contact->email,
                'company' => $contact->company,
                'favorite' => (int) $contact->favorite,
                'created_at' => $contact->createdAt->format(DateTimeImmutable::ATOM),
                'updated_at' => $contact->updatedAt->format(DateTimeImmutable::ATOM),
            ]);

            $phoneStatement = $this->pdo->prepare(
                'INSERT INTO phones (id, contact_id, type, number, position) VALUES (:id, :contact_id, :type, :number, :position)',
            );
            foreach (array_values($contact->phones) as $position => $phone) {
                $phoneStatement->execute([
                    'id' => $phone->id,
                    'contact_id' => $contact->id,
                    'type' => $phone->type->value,
                    'number' => $phone->number,
                    'position' => $position,
                ]);
            }

            $this->pdo->commit();
        } catch (\Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function delete(string $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM contacts WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    /**
     * Carga los teléfonos de varios contactos en **una sola query**, agrupados
     * por `contact_id`. Evita el problema N+1 al listar (una query por
     * teléfono en vez de una por listado completo).
     *
     * @param string[] $contactIds
     * @return array<string, Phone[]>
     */
    private function phonesForContacts(array $contactIds): array
    {
        if ($contactIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($contactIds), '?'));
        $statement = $this->pdo->prepare(
            "SELECT * FROM phones WHERE contact_id IN ({$placeholders}) ORDER BY contact_id, position",
        );
        $statement->execute($contactIds);

        $grouped = [];
        foreach ($statement->fetchAll() as $row) {
            $grouped[$row['contact_id']][] = new Phone($row['id'], PhoneType::from($row['type']), $row['number']);
        }

        return $grouped;
    }

    /**
     * @param array<string, mixed> $row
     * @param Phone[] $phones
     */
    private function hydrate(array $row, array $phones): Contact
    {
        return new Contact(
            id: $row['id'],
            firstName: $row['first_name'],
            lastName: $row['last_name'],
            email: $row['email'],
            company: $row['company'],
            favorite: (bool) $row['favorite'],
            phones: $phones,
            createdAt: new DateTimeImmutable($row['created_at']),
            updatedAt: new DateTimeImmutable($row['updated_at']),
        );
    }
}
