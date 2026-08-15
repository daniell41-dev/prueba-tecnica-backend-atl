<?php

declare(strict_types=1);

namespace App\Validation;

use App\Domain\ContactRepositoryInterface;
use App\Domain\Phone;
use App\Domain\PhoneType;
use App\Support\PhoneNumber;

/**
 * Reglas de validación del dominio "contacto" (bonus 1 y 2).
 *
 * Mismo criterio que los validadores del formulario Angular
 * (`core/validators/contact.validators.ts`): nombre con letras/acentos, email
 * único, teléfono mexicano a 10 dígitos, sin duplicados dentro del contacto.
 * Vive en su propia capa —no en el controlador ni en el repositorio— para
 * poder probarla de forma aislada y reutilizarla si aparece un endpoint de
 * edición.
 */
final class ContactValidator
{
    private const NAME_MAX_LENGTH = 60;
    private const COMPANY_MAX_LENGTH = 80;
    private const MAX_PHONES = 10;

    /** Letras (con acentos y ñ), espacios, apóstrofo y guion. */
    private const NAME_PATTERN = '/^[\p{L}\s\'\-]+$/u';

    public function __construct(private readonly ContactRepositoryInterface $repository)
    {
    }

    /**
     * @param array<string, mixed> $payload Body ya decodificado del JSON de la request.
     * @throws \App\Domain\Exception\ValidationException si algún campo no es válido.
     */
    public function validate(array $payload): ContactInput
    {
        $validator = new Validator();

        $firstName = $this->validateName($validator, 'first_name', $payload['first_name'] ?? null);
        $lastName = $this->validateName($validator, 'last_name', $payload['last_name'] ?? null);
        $email = $this->validateEmail($validator, $payload['email'] ?? null);
        $company = $this->validateCompany($validator, $payload['company'] ?? null);
        $favorite = $this->validateFavorite($validator, $payload['favorite'] ?? false);
        $phones = $this->validatePhones($validator, $payload['phones'] ?? []);

        $validator->throwIfInvalid();

        return new ContactInput(
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            company: $company,
            favorite: $favorite,
            phones: $phones,
        );
    }

    private function validateName(Validator $validator, string $field, mixed $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            $validator->addError($field, 'Este campo es obligatorio.');
            return '';
        }

        $value = trim($value);

        if (mb_strlen($value) > self::NAME_MAX_LENGTH) {
            $validator->addError($field, sprintf('No puede tener más de %d caracteres.', self::NAME_MAX_LENGTH));
        }

        if (preg_match(self::NAME_PATTERN, $value) !== 1) {
            $validator->addError($field, 'Solo se permiten letras, espacios, apóstrofos y guiones.');
        }

        return $value;
    }

    private function validateEmail(Validator $validator, mixed $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            $validator->addError('email', 'Este campo es obligatorio.');
            return '';
        }

        $value = trim($value);

        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $validator->addError('email', 'Debe ser un correo electrónico válido.');
            return $value;
        }

        if ($this->repository->emailExists($value)) {
            $validator->addError('email', 'El correo ya está registrado.');
        }

        return $value;
    }

    private function validateCompany(Validator $validator, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            $validator->addError('company', 'Debe ser una cadena de texto.');
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) > self::COMPANY_MAX_LENGTH) {
            $validator->addError('company', sprintf('No puede tener más de %d caracteres.', self::COMPANY_MAX_LENGTH));
        }

        return $value;
    }

    private function validateFavorite(Validator $validator, mixed $value): bool
    {
        if (!is_bool($value)) {
            $validator->addError('favorite', 'Debe ser verdadero o falso.');
            return false;
        }

        return $value;
    }

    /**
     * @return Phone[]
     */
    private function validatePhones(Validator $validator, mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (!is_array($value) || !array_is_list($value)) {
            $validator->addError('phones', 'Debe ser un arreglo de teléfonos.');
            return [];
        }

        if (count($value) > self::MAX_PHONES) {
            $validator->addError('phones', sprintf('No se permiten más de %d teléfonos.', self::MAX_PHONES));
            return [];
        }

        $phones = [];
        $normalizedNumbers = [];

        foreach ($value as $index => $item) {
            $field = "phones.{$index}";

            if (!is_array($item)) {
                $validator->addError($field, 'Debe ser un objeto con "type" y "number".');
                continue;
            }

            $type = $this->validatePhoneType($validator, "{$field}.type", $item['type'] ?? null);
            $number = $this->validatePhoneNumber($validator, "{$field}.number", $item['number'] ?? null);

            if ($number !== null) {
                $normalizedNumbers[$index] = $number;
            }

            if ($type !== null && $number !== null) {
                $phones[$index] = Phone::create($type, $number);
            }
        }

        $this->rejectDuplicatePhones($validator, $normalizedNumbers);

        // Si hubo cualquier error, no importa devolver una lista parcial: la
        // request completa se rechaza en `validate()` vía `throwIfInvalid()`.
        return array_values($phones);
    }

    private function validatePhoneType(Validator $validator, string $field, mixed $value): ?PhoneType
    {
        if ($value === null) {
            return PhoneType::default();
        }

        if (!is_string($value) || PhoneType::tryFrom($value) === null) {
            $allowed = implode(', ', array_map(static fn (PhoneType $type): string => $type->value, PhoneType::cases()));
            $validator->addError($field, "Debe ser uno de: {$allowed}.");
            return null;
        }

        return PhoneType::from($value);
    }

    private function validatePhoneNumber(Validator $validator, string $field, mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            $validator->addError($field, 'Este campo es obligatorio.');
            return null;
        }

        // Antes de normalizar: si se dejara pasar cualquier carácter, `normalize()`
        // borraría las letras junto con los separadores y un número con basura
        // acabaría guardándose como si fuera válido.
        if (!PhoneNumber::isWellFormed($value)) {
            $validator->addError(
                $field,
                'Solo se permiten dígitos, espacios, guiones, puntos, paréntesis y un "+" inicial.',
            );
            return null;
        }

        $normalized = PhoneNumber::normalize($value);

        if (strlen($normalized) !== PhoneNumber::DIGITS) {
            $validator->addError($field, sprintf('Debe tener %d dígitos.', PhoneNumber::DIGITS));
            return null;
        }

        return $normalized;
    }

    /** @param array<int, string> $normalizedNumbers */
    private function rejectDuplicatePhones(Validator $validator, array $normalizedNumbers): void
    {
        $counts = array_count_values($normalizedNumbers);

        foreach ($normalizedNumbers as $index => $number) {
            if ($counts[$number] > 1) {
                $validator->addError("phones.{$index}.number", 'Este número está duplicado en el mismo contacto.');
            }
        }
    }
}
