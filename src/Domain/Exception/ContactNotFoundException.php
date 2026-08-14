<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * No existe un contacto con el id solicitado. El `Kernel` la traduce a `404`.
 */
final class ContactNotFoundException extends HttpException
{
    public static function forId(string $id): self
    {
        return new self(sprintf('No existe un contacto con id "%s".', $id), 404);
    }
}
