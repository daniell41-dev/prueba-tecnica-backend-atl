<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Normalización de números telefónicos, compartida entre el seed y la
 * validación (bonus 2).
 *
 * Réplica exacta de `normalizePhoneNumber` en el adapter del frontend
 * (`src/app/core/repositories/contact.adapter.ts`): deja el número en dígitos y
 * quita la lada de México (`+52`) cuando viene incluida, para que ambos
 * proyectos guarden/muestren el mismo formato de 10 dígitos.
 */
final class PhoneNumber
{
    private const MX_COUNTRY_CODE = '52';
    public const DIGITS = 10;

    /** `+52 55 1234 5678` → `5512345678`. Deja solo dígitos y quita la lada MX si aplica. */
    public static function normalize(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw) ?? '';

        if (
            strlen($digits) === self::DIGITS + strlen(self::MX_COUNTRY_CODE)
            && str_starts_with($digits, self::MX_COUNTRY_CODE)
        ) {
            return substr($digits, strlen(self::MX_COUNTRY_CODE));
        }

        return $digits;
    }
}
