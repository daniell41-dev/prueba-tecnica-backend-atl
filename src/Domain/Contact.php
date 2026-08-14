<?php

declare(strict_types=1);

namespace App\Domain;

use App\Support\Uid;
use DateTimeImmutable;

/**
 * Contacto: entidad principal del dominio.
 *
 * Inmutable a propósito (todas las propiedades `readonly`): cualquier cambio
 * (crear con nuevos datos, etc.) construye una instancia nueva en vez de mutar
 * la existente, así que un `Contact` siempre representa un estado consistente.
 */
final class Contact
{
    /**
     * @param Phone[] $phones
     */
    public function __construct(
        public readonly string $id,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly ?string $company,
        public readonly bool $favorite,
        public readonly array $phones,
        public readonly DateTimeImmutable $createdAt,
        public readonly DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * Construye un contacto nuevo con id y timestamps generados por el servidor.
     *
     * @param Phone[] $phones
     */
    public static function createNew(
        string $firstName,
        string $lastName,
        string $email,
        ?string $company,
        bool $favorite,
        array $phones,
    ): self {
        $now = new DateTimeImmutable();

        return new self(Uid::v4(), $firstName, $lastName, $email, $company, $favorite, $phones, $now, $now);
    }

    /**
     * Forma de salida de la API: `snake_case`, igual que `ContactDto` en el
     * frontend (`src/app/core/repositories/contact.dto.ts`).
     *
     * @return array{
     *   id: string, first_name: string, last_name: string, email: string,
     *   company: string|null, favorite: bool,
     *   phones: array<int, array{type: string, number: string}>,
     *   created_at: string, updated_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'email' => $this->email,
            'company' => $this->company,
            'favorite' => $this->favorite,
            'phones' => array_map(static fn (Phone $phone): array => $phone->toArray(), $this->phones),
            'created_at' => $this->createdAt->format(DateTimeImmutable::ATOM),
            'updated_at' => $this->updatedAt->format(DateTimeImmutable::ATOM),
        ];
    }
}
