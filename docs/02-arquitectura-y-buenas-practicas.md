# 02 - ARQUITECTURA Y BUENAS PRÁCTICAS

Reglas de estructura, arquitectura y calidad de código para esta API (PHP puro, sin
framework). Adaptado del mismo esquema usado en `prueba-tecnica-fronted-atl` y
`project-mobile-ionic`, por eso el estilo y las convenciones son consistentes entre los
tres repos.

---

## 🧱 Estructura de carpetas (capas)

```
public/
├── index.php                 # front controller — único punto de entrada
└── .htaccess                 # rewrite para Apache

src/
├── autoload.php               # autoloader PSR-4 propio (App\ → src/), sin Composer
├── Kernel.php                 # despacho + traducción de excepciones a HTTP + CORS
├── Http/
│   ├── Request.php Response.php JsonResponse.php Router.php
│   ├── Middleware/Cors.php
│   └── Controllers/ContactController.php
├── Domain/
│   ├── Contact.php Phone.php PhoneType.php
│   ├── ContactRepositoryInterface.php
│   └── Exception/{HttpException,ContactNotFoundException,ValidationException}.php
├── Application/ContactService.php   # caso de uso: orquesta validación + repositorio
├── Validation/{Validator.php,ContactValidator.php,ContactInput.php}
├── Infrastructure/
│   ├── Database/{Connection.php,Migrator.php}
│   └── Persistence/PdoContactRepository.php
└── Support/{Config.php,Uid.php,PhoneNumber.php}

routes/api.php                 # mapa ruta → controlador
config/{app.php,database.php}  # configuración leída de variables de entorno
database/{migrations,seeds}/   # schema SQL (sqlite/mysql) y datos de ejemplo
bin/migrate.php                 # CLI de migraciones
tests/{Unit,Integration,Feature}/
postman/                        # colección + environment para probar desde Postman
```

### Reglas de dependencia (bajo acoplamiento)

```
Http  ──►  Application  ──►  Domain  ◄──  Infrastructure
              │                              │
              └──────────► Validation ◄──────┘
```

- `Http` (controladores, router, kernel) solo conoce `Application` y `Domain`
  (excepciones). Nunca instancia PDO ni SQL directamente.
- `Application` (`ContactService`) orquesta `Validation` y `Domain\ContactRepositoryInterface`
  — nunca la implementación concreta.
- `Domain` no depende de ninguna otra capa. Es el centro: entidades, contrato del
  repositorio, excepciones.
- `Infrastructure` implementa los contratos de `Domain` (Dependency Inversion) — es la
  única capa que sabe que existe PDO/SQL.
- `Validation` depende de `Domain` (para consultar `emailExists` contra el repositorio) y
  de `Support` (normalización de teléfonos), no de `Http` ni `Infrastructure`.

### Flujo de una request

```
public/index.php  (arma dependencias a mano, sin contenedor de DI — YAGNI)
   → Kernel::handle(Request)
      → Router::match()                    404 / 405
         → ContactController::{index,show,store,destroy}
            → ContactService                caso de uso
               → ContactValidator            bonus 1 y 2 → 422
               → ContactRepositoryInterface  (Dependency Inversion)
                  → PdoContactRepository     SQL vía PDO, transaccional
                     → Connection            PDO configurado por config/database.php
   ← JsonResponse / Response  (+ headers CORS, siempre)
```

---

## 📛 Convenciones de nombres

| Tipo | Sufijo / patrón | Ejemplo |
|------|-----------------|---------|
| Controlador | `*Controller.php` | `ContactController.php` |
| Servicio de aplicación | `*Service.php` | `ContactService.php` |
| Repositorio (interfaz) | `*RepositoryInterface.php` | `ContactRepositoryInterface.php` |
| Repositorio (implementación) | `Pdo*Repository.php` | `PdoContactRepository.php` |
| Validador | `*Validator.php` | `ContactValidator.php` |
| Excepción | `*Exception.php` | `ContactNotFoundException.php` |
| Entidad de dominio | `PascalCase.php`, sin sufijo | `Contact.php`, `Phone.php` |

- **Archivos:** un namespace/clase pública por archivo, `PascalCase.php` (PSR-4).
- **Namespaces:** `App\<Capa>\...`, reflejando la ruta bajo `src/`.
- **Clases:** `PascalCase`. **Métodos/propiedades:** `camelCase`.
- **PSR-12** como guía de estilo (4 espacios, `declare(strict_types=1)` en todo archivo).

---

## 🛠️ Patrones de diseño aplicados

- **Repository** — `ContactRepositoryInterface` (Domain) + `PdoContactRepository`
  (Infrastructure) separan el "qué" del "dónde", tal como recomienda el enunciado.
- **Adapter/Value Object** — `Contact::toArray()` / `Phone::toArray()` traducen el modelo
  de dominio al `snake_case` del contrato HTTP (mismo DTO que consume el frontend
  Angular), sin que el dominio conozca ese formato de salida.
- **Facade** — `ContactService` es la única puerta de entrada al caso de uso "contactos"
  para el controlador: no sabe de SQL ni de las reglas exactas de validación.
- **Front Controller** — `public/index.php` es el único punto de entrada HTTP.
- **Strategy (implícito)** — cambiar de SQLite a MySQL es una variable de entorno
  (`DB_DRIVER`); `PdoContactRepository` no cambia porque usa SQL estándar.
- **Value Object inmutable** — `Contact`, `Phone` con propiedades `readonly`: cualquier
  cambio construye una instancia nueva.

### SOLID

- **S**RP — cada clase tiene una responsabilidad: el controlador traduce HTTP, el
  servicio orquesta el caso de uso, el validador solo valida, el repositorio solo
  persiste.
- **O**CP — agregar una fuente de datos nueva es agregar una clase que implemente
  `ContactRepositoryInterface`, sin tocar `ContactService` ni el controlador.
- **L**SP — cualquier implementación de `ContactRepositoryInterface` es intercambiable
  detrás de la interfaz (hoy `PdoContactRepository`; en los tests, `InMemoryContactRepository`).
- **I**SP — `ContactInput` (salida de la validación) expone solo los campos que
  `Contact::createNew()` necesita, nada de detalles de la request HTTP.
- **D**IP — `ContactService` y `ContactValidator` dependen de `ContactRepositoryInterface`,
  nunca de PDO directamente.

### DRY / KISS / YAGNI

- **DRY** — la normalización de teléfonos (`Support\PhoneNumber`) es una sola función,
  usada tanto por el seed como por la validación; los mensajes de error de validación
  viven en un solo lugar (`ContactValidator`).
- **KISS** — sin contenedor de inyección de dependencias (las ~6 clases de
  `public/index.php` se arman a mano); sin ORM (SQL directo con PDO, suficiente para dos
  tablas).
- **YAGNI** — no se implementa `PUT`/`PATCH` (el enunciado pide alta, listado y baja); no
  hay autenticación (no la pide el ejercicio); no hay un framework de migraciones versionado
  (con dos tablas, `CREATE TABLE IF NOT EXISTS` es suficiente y más simple de auditar).

---

## 🧪 Testing

- **Unit** (`tests/Unit/`) — `PhoneNumberTest` (normalización), `RouterTest` (params,
  404 vs 405), `ContactValidatorTest` (reglas del bonus 1 y 2) contra un repositorio en
  memoria (`tests/Support/InMemoryContactRepository.php`), sin tocar PDO.
- **Integration** (`tests/Integration/`) — `PdoContactRepositoryTest` contra SQLite en
  memoria: alta transaccional, listado sin N+1, borrado en cascada, unicidad de email.
- **Feature** (`tests/Feature/`) — `ContactApiTest` arma el mismo stack que
  `public/index.php` (Kernel real) y ejercita los 4 endpoints y los códigos de error
  (400/404/405/422) pasando un `Request` directamente al `Kernel`, sin levantar un
  servidor HTTP.
- `composer test` corre las tres suites (ver `phpunit.xml`).

---

## ✅ Definition of Done (por tarea)

- [ ] Cumple la estructura de carpetas, namespaces y convenciones de nombres.
- [ ] `declare(strict_types=1)` y tipado explícito en parámetros/retornos.
- [ ] Sin errores de `composer lint` (`php -l`).
- [ ] Lógica de negocio en `Application`/`Domain`/`Validation`; HTTP y persistencia
  aisladas en `Http`/`Infrastructure`.
- [ ] Tests unitarios/de integración de la lógica nueva; `composer test` en verde.
- [ ] Sin `var_dump`/`echo` de debug ni código muerto.
- [ ] Commit con Conventional Commits.
