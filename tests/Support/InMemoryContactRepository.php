<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Contact;
use App\Domain\ContactRepositoryInterface;

/**
 * Implementación en memoria de `ContactRepositoryInterface`, usada por los
 * tests unitarios (p. ej. `ContactValidatorTest`) que necesitan un repositorio
 * real para probar la unicidad de email, pero no quieren pagar el costo ni el
 * acoplamiento de tocar PDO/SQLite — esa cobertura vive en
 * `tests/Integration/PdoContactRepositoryTest`.
 */
final class InMemoryContactRepository implements ContactRepositoryInterface
{
    /** @var array<string, Contact> */
    private array $contacts = [];

    public function all(): array
    {
        $contacts = array_values($this->contacts);
        usort(
            $contacts,
            static fn (Contact $a, Contact $b): int => [$a->firstName, $a->lastName] <=> [$b->firstName, $b->lastName],
        );

        return $contacts;
    }

    public function find(string $id): ?Contact
    {
        return $this->contacts[$id] ?? null;
    }

    public function emailExists(string $email, ?string $excludeId = null): bool
    {
        foreach ($this->contacts as $contact) {
            if ($contact->id === $excludeId) {
                continue;
            }
            if (strcasecmp($contact->email, $email) === 0) {
                return true;
            }
        }

        return false;
    }

    public function create(Contact $contact): void
    {
        $this->contacts[$contact->id] = $contact;
    }

    public function delete(string $id): bool
    {
        if (!isset($this->contacts[$id])) {
            return false;
        }

        unset($this->contacts[$id]);

        return true;
    }
}
