# CLAUDE.md

Guía operativa para Claude (y cualquier dev) en este repositorio. Léela antes de trabajar.

## Proyecto

**API de contactos** — prueba técnica backend. API REST en **PHP puro (sin framework)**
para agregar, listar y eliminar contactos, con arquitectura en capas (`Http / Application /
Domain / Infrastructure`) y persistencia en **SQLite vía PDO**. Habla el mismo contrato de
datos que el proyecto hermano `prueba-tecnica-fronted-atl` (Angular).

## Gestor de dependencias: Composer (solo para tests)

⚠️ La API **no depende de Composer para arrancar**: `src/autoload.php` es un autoloader
PSR-4 propio de ~15 líneas. Composer solo hace falta para correr la suite de PHPUnit.

```bash
composer install              # instalar phpunit (dev)
composer serve                # dev server → http://localhost:8000
composer migrate -- --fresh --seed   # crear/recrear database/contacts.sqlite + semilla
composer test                 # PHPUnit (unit + integration + feature)
composer lint                 # php -l sobre src/, public/, bin/
```

Equivalentes sin Composer:

```bash
php -S localhost:8000 -t public public/index.php
php bin/migrate.php --fresh --seed
```

## Antes de terminar una tarea

Ejecuta y deja en verde:

```bash
composer lint && composer test
```

Y prueba manualmente al menos un endpoint con `curl` o la colección de Postman
(`postman/contacts-api.postman_collection.json`).

## Convenciones

- **Commits:** Conventional Commits (`feat:`, `fix:`, `docs:`, `refactor:`, `test:`,
  `chore:`, `ci:`). Ver `docs/01-flujo-git-github.md`.
- **Ramas:** rama de sesión / `feature/<desc>` desde `develop`; PR a `develop`; PR
  `develop → main` solo a petición explícita.
- **Arquitectura y estilo:** `docs/02-arquitectura-y-buenas-practicas.md` (capas
  `Http / Application / Domain / Infrastructure`, PSR-12, SOLID, DRY/KISS/YAGNI).
- **Decisiones técnicas:** `docs/03-decisiones-tecnicas.md`.
- **Contrato de la API:** `docs/04-api-reference.md`.

## Documentación del repo

- `README.md` — qué es la API, paso a paso para correrla, ejemplos curl/Postman.
- `docs/01-flujo-git-github.md` — flujo Git/GitHub.
- `docs/02-arquitectura-y-buenas-practicas.md` — arquitectura, convenciones, SOLID.
- `docs/03-decisiones-tecnicas.md` — por qué de cada decisión técnica relevante.
- `docs/04-api-reference.md` — referencia completa de endpoints.
