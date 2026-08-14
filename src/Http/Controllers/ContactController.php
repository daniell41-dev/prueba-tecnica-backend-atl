<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\ContactService;
use App\Domain\Contact;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Response;

/**
 * Traduce HTTP ↔ caso de uso, nada más: no valida, no toca SQL, no arma
 * mensajes de negocio. Toda esa lógica vive en `ContactService` y las capas
 * por debajo; este controlador solo lee el `Request` y arma el `Response`.
 */
final class ContactController
{
    public function __construct(private readonly ContactService $service)
    {
    }

    /** GET /api/contacts */
    public function index(Request $request): JsonResponse
    {
        $contacts = array_map(
            static fn (Contact $contact): array => $contact->toArray(),
            $this->service->list(),
        );

        return new JsonResponse(['contacts' => $contacts]);
    }

    /** GET /api/contacts/{id} */
    public function show(Request $request): JsonResponse
    {
        $contact = $this->service->find($request->routeParams['id']);

        return new JsonResponse(['contact' => $contact->toArray()]);
    }

    /** POST /api/contacts */
    public function store(Request $request): JsonResponse
    {
        $contact = $this->service->create($request->json());

        return new JsonResponse(
            ['contact' => $contact->toArray()],
            201,
            ['Location' => "/api/contacts/{$contact->id}"],
        );
    }

    /** DELETE /api/contacts/{id} */
    public function destroy(Request $request): Response
    {
        $this->service->delete($request->routeParams['id']);

        return new Response(204);
    }
}
