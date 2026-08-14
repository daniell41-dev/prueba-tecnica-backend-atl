<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * Contrato de acceso a datos de contactos (patrón Repository).
 *
 * Separa el "qué" (operaciones de datos disponibles) del "dónde" (SQLite, MySQL,
 * memoria...). `Application\ContactService` depende únicamente de esta interfaz,
 * nunca de PDO directamente — es la recomendación del enunciado de "separar el
 * acceso a los datos" llevada a Dependency Inversion.
 */
interface ContactRepositoryInterface
{
    /**
     * Todos los contactos, ordenados por nombre.
     *
     * @return Contact[]
     */
    public function all(): array;

    public function find(string $id): ?Contact;

    /**
     * Si ya existe un contacto con ese correo (case-insensitive).
     *
     * @param string|null $excludeId Ignora este id al comparar (útil al editar; hoy
     *                               no hay endpoint de edición, pero deja el contrato listo).
     */
    public function emailExists(string $email, ?string $excludeId = null): bool;

    public function create(Contact $contact): void;

    /** @return bool `true` si existía y se eliminó; `false` si no existía. */
    public function delete(string $id): bool;
}
