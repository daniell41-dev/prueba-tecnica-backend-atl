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

    /**
     * Caracteres admitidos al capturar un teléfono: dígitos y los separadores
     * habituales de formato, con un `+` opcional al inicio para la lada.
     */
    private const FORMAT_PATTERN = '/^\+?[0-9\s\-().]+$/';

    /**
     * Si el texto solo contiene caracteres propios de un teléfono.
     *
     * Hay que preguntarlo **antes** de normalizar: `normalize()` descarta todo
     * lo que no sea dígito, así que no distingue un separador legítimo de basura
     * — sin esta comprobación, `"+52 abc55 1234 5678"` se convertiría en un
     * número de 10 dígitos perfectamente válido y se guardaría como tal.
     */
    public static function isWellFormed(string $raw): bool
    {
        return preg_match(self::FORMAT_PATTERN, trim($raw)) === 1;
    }

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
