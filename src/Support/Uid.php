<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Generador de identificadores únicos para contactos y teléfonos.
 *
 * Usa UUID v4 (vía `random_bytes`, sin depender de la extensión `uuid`) para que
 * los ids sean opacos y no revelen el orden de creación ni el conteo de filas.
 */
final class Uid
{
    public static function v4(): string
    {
        $bytes = random_bytes(16);

        // Versión 4 y variante RFC 4122.
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20, 12),
        );
    }
}
