<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Etiquetas válidas para un teléfono (bonus 2).
 *
 * Mismo vocabulario que `PhoneLabel` en el frontend Angular
 * (`src/app/core/models/contact.model.ts`), para que ambos ejercicios hablen el
 * mismo idioma sin traducciones adicionales.
 */
enum PhoneType: string
{
    case Mobile = 'mobile';
    case Home = 'home';
    case Work = 'work';
    case Other = 'other';

    public static function default(): self
    {
        return self::Mobile;
    }
}
