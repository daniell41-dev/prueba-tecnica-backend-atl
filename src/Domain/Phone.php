<?php

declare(strict_types=1);

namespace App\Domain;

use App\Support\Uid;

/**
 * Un teléfono de un contacto. Un contacto puede tener cero, uno o varios (bonus 2).
 *
 * `$number` se guarda siempre normalizado a solo dígitos: la normalización
 * (quitar lada `+52`, espacios, guiones, paréntesis) ocurre en la capa de
 * validación antes de construir este objeto, igual que hace
 * `normalizePhoneNumber` en el adapter del frontend.
 */
final class Phone
{
    public function __construct(
        public readonly string $id,
        public readonly PhoneType $type,
        public readonly string $number,
    ) {
    }

    public static function create(PhoneType $type, string $number): self
    {
        return new self(Uid::v4(), $type, $number);
    }

    /**
     * Forma de salida de la API: solo `type`/`number`, igual que `PhoneDto` en el
     * frontend. El `id` es un detalle de persistencia, no del contrato público.
     *
     * @return array{type: string, number: string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'number' => $this->number,
        ];
    }
}
