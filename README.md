# API de contactos — prueba técnica Backend

API REST en **PHP puro (sin framework)** para gestionar una lista de contactos: agregar,
listar y eliminar, con arquitectura en capas que separa el acceso a los datos
(SQLite vía PDO). Incluye validación completa (bonus 1) y uno o varios teléfonos por
contacto (bonus 2).

> ⏱️ **Tiempo invertido:** _completar antes de entregar_ — ver la nota al final de este
> README.

---

## Índice

- [Funcionalidad](#funcionalidad)
- [Stack](#stack)
- [Cómo correr el proyecto (paso a paso)](#cómo-correr-el-proyecto-paso-a-paso)
- [Scripts disponibles](#scripts-disponibles)
- [Endpoints](#endpoints)
- [Probar con Postman](#probar-con-postman)
- [Estructura del proyecto](#estructura-del-proyecto)
- [Arquitectura](#arquitectura)
- [Testing](#testing)
- [Conectar el frontend Angular (proyecto hermano)](#conectar-el-frontend-angular-proyecto-hermano)
- [Migrar de SQLite a MySQL](#migrar-de-sqlite-a-mysql)
- [Flujo de Git](#flujo-de-git)
- [Documentación adicional](#documentación-adicional)

---

## Funcionalidad

- **Listar contactos** — `GET /api/contacts`, ordenados por nombre.
- **Obtener un contacto** — `GET /api/contacts/{id}`.
- **Crear un contacto** — `POST /api/contacts`: nombre, apellido, email, empresa
  (opcional), favorito (opcional) y **uno o varios teléfonos** (bonus 2), cada uno con
  tipo (móvil/casa/trabajo/otro) y número.
- **Eliminar un contacto** — `DELETE /api/contacts/{id}` (borra también sus teléfonos).
- **Validación completa (bonus 1):** nombre/apellido/email requeridos y no vacíos, email
  con formato válido y único, teléfonos a 10 dígitos sin duplicados dentro del mismo
  contacto, límites de longitud. Errores devueltos por campo en un `422`.
- **Persistencia real en SQLite** (vía PDO) detrás de un `ContactRepositoryInterface`:
  migrar a MySQL es cambiar variables de entorno, no código.

## Stack

- **PHP 8.1+**, sin framework — autoloader PSR-4 propio (`src/autoload.php`)
- **PDO** + **SQLite** (extensión incluida en PHP; MySQL soportado por configuración)
- **PHPUnit 10** para tests (unit, integración, feature)
- **Composer** — solo para dependencias de desarrollo (PHPUnit); la API no lo necesita
  para arrancar

---

## Cómo correr el proyecto (paso a paso)

### 1. Requisitos previos

- **PHP 8.1 o superior**, con las extensiones `pdo` y `pdo_sqlite` (vienen por defecto en
  la mayoría de instalaciones de PHP).
- **Composer** — opcional; solo hace falta para correr los tests.

### 2. Clonar

```bash
git clone <url-del-repo>
cd prueba-tecnica-backend-atl
```

### 3. Crear la base de datos y sembrar los datos de ejemplo

```bash
php bin/migrate.php --fresh --seed
```

Esto crea `database/contacts.sqlite` con las tablas `contacts`/`phones` y carga 8
contactos de ejemplo (mismos datos que usa el proyecto frontend hermano, con teléfonos
normalizados a 10 dígitos).

### 4. Levantar el servidor

```bash
php -S localhost:8000 -t public public/index.php
```

La API queda disponible en **http://localhost:8000/api/contacts**.

### 5. Probar

```bash
curl http://localhost:8000/api/contacts
```

Deberías ver los 8 contactos de ejemplo. Ver la [referencia completa de la
API](docs/04-api-reference.md) o la [colección de Postman](#probar-con-postman) para
todos los endpoints y casos de error.

### 6. Verificar que todo está en verde (opcional pero recomendado)

```bash
composer install       # instala PHPUnit (dev)
composer lint           # php -l sobre src/, public/, bin/
composer test            # 37 pruebas unitarias, de integración y de feature
```

---

## Scripts disponibles

| Comando | Qué hace |
|---|---|
| `php -S localhost:8000 -t public public/index.php` | Servidor embebido de PHP (`composer serve` hace lo mismo) |
| `php bin/migrate.php` | Crea las tablas si no existen |
| `php bin/migrate.php --fresh` | Borra y vuelve a crear las tablas |
| `php bin/migrate.php --fresh --seed` | Igual que arriba + carga los 8 contactos de ejemplo |
| `composer install` | Instala PHPUnit (dev); no hace falta para correr la API |
| `composer test` | Corre la suite de PHPUnit (37 tests) |
| `composer lint` | `php -l` sobre `src/`, `public/`, `bin/` |

---

## Endpoints

| Método | Ruta | Descripción | Éxito | Errores |
|---|---|---|---|---|
| `GET` | `/api/contacts` | Listar contactos | `200` | — |
| `GET` | `/api/contacts/{id}` | Obtener un contacto | `200` | `404` |
| `POST` | `/api/contacts` | Crear contacto | `201` + `Location` | `400`, `422` |
| `DELETE` | `/api/contacts/{id}` | Eliminar contacto | `204` | `404` |

Ejemplo de creación:

```bash
curl -X POST http://localhost:8000/api/contacts \
  -H 'Content-Type: application/json' \
  -d '{
    "first_name": "Ada",
    "last_name": "Lovelace",
    "email": "ada@example.com",
    "company": "Analytical Engine",
    "phones": [
      { "type": "mobile", "number": "+52 55 1111 2222" },
      { "type": "work", "number": "5533334444" }
    ]
  }'
```

Ejemplo de error de validación (bonus 1: nada vacío):

```bash
curl -X POST http://localhost:8000/api/contacts \
  -H 'Content-Type: application/json' \
  -d '{"first_name": "", "last_name": "", "email": ""}'
# 422 → {"message":"Los datos enviados no son válidos.","errors":{"first_name":[...],"last_name":[...],"email":[...]}}
```

Referencia completa (todos los campos, reglas y códigos de error) en
**[`docs/04-api-reference.md`](docs/04-api-reference.md)**.

## Probar con Postman

1. Importa `postman/contacts-api.postman_collection.json` y
   `postman/local.postman_environment.json`.
2. Selecciona el environment **"Contacts API - Local"** (`base_url` apunta a
   `http://localhost:8000`).
3. Corre la carpeta **"Contactos"** de arriba hacia abajo — "Crear contacto" guarda el
   `contact_id` que usan "Obtener" y "Eliminar".
4. La carpeta **"Casos de error"** cubre campos vacíos, email inválido/duplicado, teléfono
   inválido, JSON malformado, `404` y `405`, y se puede correr en cualquier momento.

Cada request trae sus propios tests (pestaña *Tests* de Postman) que validan el código de
estado y la forma de la respuesta.

---

## Estructura del proyecto

```
public/index.php               # front controller — único punto de entrada
src/
├── autoload.php                 # autoloader PSR-4 propio (sin Composer en runtime)
├── Kernel.php                    # despacho + traducción de excepciones a HTTP + CORS
├── Http/                          # Request, Response, Router, controlador
├── Domain/                        # Contact, Phone, contrato del repositorio, excepciones
├── Application/ContactService.php # caso de uso: valida y persiste
├── Validation/                    # reglas del bonus 1 y 2
├── Infrastructure/                # PDO, migraciones, PdoContactRepository
└── Support/                       # Config, Uid, normalización de teléfonos

routes/api.php                 # mapa ruta → controlador
database/{migrations,seeds}/    # schema SQL (SQLite y MySQL) y datos de ejemplo
bin/migrate.php                  # CLI de migraciones
tests/{Unit,Integration,Feature}/
postman/                          # colección + environment
```

## Arquitectura

Capas `Http → Application → Domain ← Infrastructure` con **Repository** (acceso a datos
detrás de una interfaz, la recomendación explícita del enunciado), **Facade**
(`ContactService`) y **Front Controller**. Detalle completo, convenciones y SOLID
aplicado en **[`docs/02-arquitectura-y-buenas-practicas.md`](docs/02-arquitectura-y-buenas-practicas.md)**.
El porqué de cada decisión relevante (SQLite sin Composer en runtime, email obligatorio,
dos tablas para los teléfonos, contrato compatible con el frontend, etc.) está en
**[`docs/03-decisiones-tecnicas.md`](docs/03-decisiones-tecnicas.md)**.

## Testing

Suite de **37 pruebas** con PHPUnit en tres niveles — unit (validación, router,
normalización de teléfonos), integración (repositorio contra SQLite en memoria) y feature
(API completa de punta a punta vía el `Kernel` real):

```bash
composer install
composer test
```

## Conectar el frontend Angular (proyecto hermano)

Esta API es un ejercicio independiente del frontend (`prueba-tecnica-fronted-atl`), pero
habla **el mismo contrato de datos** (`snake_case`, envelope `{"contacts": [...]}`,
teléfonos normalizados a 10 dígitos) que ya consume `ContactsApiService` en ese repo. Para
conectarlos, sin tocar el adapter ni el resto del código Angular:

1. Levanta esta API: `php -S localhost:8000 -t public public/index.php`.
2. En `prueba-tecnica-fronted-atl/src/environments/environment.ts`, cambia:
   ```ts
   contactsApiUrl: 'http://localhost:8000/api/contacts',
   ```
3. `pnpm start` en el frontend — la lista carga desde esta API real en vez del JSON
   estático. (El frontend hoy solo hace `GET`; el resto de operaciones seguirán yendo a
   `localStorage`, tal como está diseñado ese repo — ver su propio `README.md`.)

## Migrar de SQLite a MySQL

`PdoContactRepository` usa SQL estándar; cambiar de motor es configuración:

```bash
export DB_DRIVER=mysql
export DB_HOST=127.0.0.1
export DB_DATABASE=contacts_api
export DB_USERNAME=root
export DB_PASSWORD=secret
mysql -u root -p contacts_api < database/migrations/schema.mysql.sql
php -S localhost:8000 -t public public/index.php
```

## Flujo de Git

Este repo sigue Conventional Commits y un flujo `rama de sesión → develop → main`
documentado en **[`docs/01-flujo-git-github.md`](docs/01-flujo-git-github.md)**.

## Documentación adicional

- [`docs/01-flujo-git-github.md`](docs/01-flujo-git-github.md) — flujo Git/GitHub.
- [`docs/02-arquitectura-y-buenas-practicas.md`](docs/02-arquitectura-y-buenas-practicas.md) — arquitectura, convenciones, SOLID.
- [`docs/03-decisiones-tecnicas.md`](docs/03-decisiones-tecnicas.md) — decisiones técnicas y alternativas descartadas.
- [`docs/04-api-reference.md`](docs/04-api-reference.md) — referencia completa de la API.

---

### Nota sobre el tiempo invertido

Este proyecto se generó con **Claude Code** en una sesión de trabajo asistida por IA. No
tengo un cronómetro real de "horas-persona" para reportar de forma honesta en tu nombre —
te recomiendo reemplazar la línea de arriba con el tiempo que tú realmente dediques a
revisar, entender y ajustar el código antes de entregarlo, en vez de dejar un número
inventado.
