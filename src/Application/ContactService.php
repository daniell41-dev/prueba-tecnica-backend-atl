<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Contact;
use App\Domain\ContactRepositoryInterface;
use App\Domain\Exception\ContactNotFoundException;
use App\Validation\ContactValidator;

/**
 * Caso de uso "gestionar contactos" (Facade sobre validación + repositorio).
 *
 * Es la única puerta de entrada que conoce `ContactController`: no sabe de
 * PDO ni de reglas de validación, solo de este servicio. Mantiene al
 * controlador delgado (traduce HTTP ↔ caso de uso, nada más) y a la lógica de
 * negocio fuera de la capa HTTP.
 */
final class ContactService
{
    public function __construct(
        private readonly ContactRepositoryInterface $repository,
        private readonly ContactValidator $validator,
    ) {
    }

    /** @return Contact[] */
    public function list(): array
    {
        return $this->repository->all();
    }

    public function find(string $id): Contact
    {
        return $this->repository->find($id) ?? throw ContactNotFoundException::forId($id);
    }

    /**
     * @param array<string, mixed> $payload Body ya decodificado del JSON de la request.
     * @throws \App\Domain\Exception\ValidationException si los datos no son válidos.
     */
    public function create(array $payload): Contact
    {
        $input = $this->validator->validate($payload);

        $contact = Contact::createNew(
            firstName: $input->firstName,
            lastName: $input->lastName,
            email: $input->email,
            company: $input->company,
            favorite: $input->favorite,
            phones: $input->phones,
        );

        $this->repository->create($contact);

        return $contact;
    }

    /** @throws ContactNotFoundException si no existe un contacto con ese id. */
    public function delete(string $id): void
    {
        if (!$this->repository->delete($id)) {
            throw ContactNotFoundException::forId($id);
        }
    }
}
