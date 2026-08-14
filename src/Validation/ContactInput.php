<?php

declare(strict_types=1);

namespace App\Validation;

use App\Domain\Phone;

/**
 * Datos de un contacto ya validados y normalizados, listos para construir un
 * `Contact` de dominio. Es la salida de `ContactValidator::validate()`.
 */
final class ContactInput
{
    /** @param Phone[] $phones */
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $email,
        public readonly ?string $company,
        public readonly bool $favorite,
        public readonly array $phones,
    ) {
    }
}
