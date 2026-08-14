# 04 - REFERENCIA DE LA API

Todas las rutas están bajo `/api`. Todas las respuestas son JSON
(`Content-Type: application/json; charset=utf-8`), salvo el `204` de `DELETE`, que no
tiene cuerpo. CORS está habilitado para cualquier origen (`Access-Control-Allow-Origin: *`
por defecto, configurable con `CORS_ALLOWED_ORIGIN`).

## Modelo de contacto

```jsonc
{
  "id": "9f1c2e3a-...",
  "first_name": "María",
  "last_name": "González",
  "email": "maria.gonzalez@atlantis.mx",
  "company": "Atlantis Labs",       // string | null
  "favorite": true,
  "phones": [
    { "type": "mobile", "number": "5512345678" }  // number: siempre 10 dígitos
  ],
  "created_at": "2026-01-12T10:30:00+00:00",  // ISO-8601
  "updated_at": "2026-01-12T10:30:00+00:00"
}
```

`type` de teléfono ∈ `mobile | home | work | other`.

---

## `GET /api/contacts`

Lista todos los contactos, ordenados por nombre.

**Respuesta `200`**

```json
{ "contacts": [ { "...": "ver Modelo de contacto" } ] }
```

```bash
curl -s http://localhost:8000/api/contacts
```

---

## `GET /api/contacts/{id}`

Obtiene un contacto por id.

**Respuesta `200`**

```json
{ "contact": { "...": "ver Modelo de contacto" } }
```

**Respuesta `404`** — no existe un contacto con ese id.

```json
{ "message": "No existe un contacto con id \"no-existe\"." }
```

```bash
curl -s http://localhost:8000/api/contacts/9f1c2e3a-...
```

---

## `POST /api/contacts`

Crea un contacto.

**Body**

| Campo | Tipo | Obligatorio | Reglas |
|---|---|---|---|
| `first_name` | string | sí | no vacío, máx. 60, solo letras/acentos/espacios/`'`/`-` |
| `last_name` | string | sí | igual que `first_name` |
| `email` | string | sí | formato válido, único (case-insensitive) |
| `company` | string \| null | no | máx. 80 |
| `favorite` | boolean | no | por defecto `false` |
| `phones` | array | no | 0 a 10 elementos; por defecto `[]` |
| `phones[].type` | string | no | `mobile\|home\|work\|other`; por defecto `mobile` |
| `phones[].number` | string | sí (si hay teléfono) | 10 dígitos tras normalizar; acepta `+52`, espacios, guiones, paréntesis; sin duplicados en el mismo contacto |

`id`, `created_at`, `updated_at` los genera el servidor: si vienen en el body, se ignoran.

**Respuesta `201`** — header `Location: /api/contacts/{id}` + el contacto creado.

```json
{ "contact": { "...": "ver Modelo de contacto" } }
```

**Respuesta `422`** — algún campo no pasa las reglas. `errors` agrupa mensajes por campo;
los teléfonos usan la clave `phones.<índice>.<campo>`.

```json
{
  "message": "Los datos enviados no son válidos.",
  "errors": {
    "email": ["El correo ya está registrado."],
    "phones.0.number": ["Debe tener 10 dígitos."]
  }
}
```

**Respuesta `400`** — el body no es JSON válido.

```json
{ "message": "El cuerpo de la solicitud no es JSON válido." }
```

```bash
curl -s -X POST http://localhost:8000/api/contacts \
  -H 'Content-Type: application/json' \
  -d '{
    "first_name": "Ada",
    "last_name": "Lovelace",
    "email": "ada@example.com",
    "company": "Analytical Engine",
    "favorite": true,
    "phones": [
      { "type": "mobile", "number": "+52 55 1111 2222" },
      { "type": "work", "number": "5533334444" }
    ]
  }'
```

---

## `DELETE /api/contacts/{id}`

Elimina un contacto (y sus teléfonos, en cascada).

**Respuesta `204`** — sin cuerpo.

**Respuesta `404`** — no existe un contacto con ese id.

```bash
curl -i -X DELETE http://localhost:8000/api/contacts/9f1c2e3a-...
```

---

## Otros códigos de error

| Código | Cuándo | Cuerpo |
|---|---|---|
| `404` | Ruta inexistente (además de "contacto no encontrado") | `{"message": "La ruta solicitada no existe."}` |
| `405` | Ruta existente con método no soportado (incluye header `Allow`) | `{"message": "Método no permitido."}` |
| `500` | Error inesperado | `{"message": "Ocurrió un error inesperado."}` (con `APP_DEBUG=true`, incluye detalle) |

## `OPTIONS`

Cualquier ruta responde `204` con los headers CORS a un preflight `OPTIONS`, sin pasar por
el enrutador de negocio.

---

## Probar desde Postman

Importa `postman/contacts-api.postman_collection.json` y
`postman/local.postman_environment.json`, selecciona el environment **"Contacts API -
Local"** y corre la carpeta **"Contactos"** de arriba hacia abajo (Crear guarda
`contact_id` para las siguientes requests). La carpeta **"Casos de error"** cubre 422,
400, 404 y 405 y se puede correr en cualquier momento.
