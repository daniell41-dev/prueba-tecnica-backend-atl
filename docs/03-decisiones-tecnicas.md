# 03 - DECISIONES TÉCNICAS

Registro corto de las decisiones de diseño tomadas para este ejercicio y por qué, con las
alternativas que se descartaron.

---

## "Sin framework" también significa sin Composer en runtime

`src/autoload.php` es un autoloader PSR-4 propio de ~15 líneas para el namespace `App\`.
`public/index.php` solo lo necesita a él: la API arranca con `php -S localhost:8000 -t
public public/index.php` sin correr `composer install` antes. `composer.json` declara el
mismo mapeo PSR-4 (autocompletado del IDE) y las dependencias de desarrollo (PHPUnit),
pero son opcionales para correr la API — solo hacen falta para ejecutar la suite de tests.

**Alternativa descartada:** usar `vendor/autoload.php` de Composer también en producción.
Es lo habitual en cualquier proyecto PHP real, pero para una prueba técnica que pide
explícitamente "sin framework", depender de Composer para lo más básico (cargar clases)
diluye un poco esa premisa. El autoloader propio es una demostración más honesta de qué
hace falta realmente para servir la API.

---

## Persistencia: SQLite vía PDO, con MySQL como configuración

`config/database.php` define el driver (`DB_DRIVER`, por defecto `sqlite`) y
`Infrastructure\Database\Connection` arma el DSN correspondiente. `PdoContactRepository`
usa solo SQL estándar (sin funciones específicas de un motor), así que cambiar a MySQL es
variables de entorno, no código — el schema equivalente vive en
`database/migrations/schema.mysql.sql`.

**Por qué SQLite y no MySQL por defecto:** la extensión `pdo_sqlite` viene con PHP (no
hay que instalar ni levantar un servidor de base de datos aparte), y el archivo se crea
solo la primera vez que corre la app. Para quien revise el ejercicio, clonar y correr sin
fricción pesa más que parecerse a un entorno de producción real.

**Por qué no un archivo JSON:** el enunciado pide explícitamente "separar el acceso a los
datos" con buenas prácticas de arquitectura en capas — un archivo JSON con lectura y
reescritura completa en cada operación no ejercita nada de lo que una capa de persistencia
real resuelve (transacciones, índices, integridad referencial). SQLite consigue la misma
fricción cero para correr el ejercicio sin renunciar a eso.

---

## Dos tablas (`contacts` + `phones`), no columnas de teléfono fijas

El bonus 2 pide "uno o varios" teléfonos por contacto, así que `phones` es una tabla
aparte con `contact_id` (1:N) y `ON DELETE CASCADE`, en vez de columnas
`phone_1`/`phone_2`/... en `contacts`. `PdoContactRepository::all()` carga los teléfonos
de **todos** los contactos listados en una sola query adicional (agrupados por
`contact_id` en PHP), no una query por contacto — evita el problema N+1 sin necesitar un
ORM con eager loading.

---

## Email obligatorio y único (bonus 1), aunque el JSON semilla del frontend tenía uno en `null`

El enunciado lista el email entre los datos del contacto y el bonus 1 pide no permitir
datos vacíos, así que aquí `email` es **requerido** y único (case-insensitive). El seed de
este repo (`database/seeds/contacts.json`) es una copia de los 8 contactos del frontend
con un correo asignado al único que tenía `email: null`, para poder sembrar sin violar esa
regla. Es una validación más estricta que la de lectura del frontend, que sigue
funcionando igual: sigue aceptando y mostrando contactos con `email: null` si la fuente de
datos se lo entrega (esta API simplemente nunca produce uno).

---

## El contrato de la API es el mismo DTO que ya consume el frontend Angular

`Contact::toArray()` devuelve `snake_case` con el mismo envelope
(`{"contacts": [...] }`) y las mismas claves (`first_name`, `company`, `favorite`,
`phones[].type`, `phones[].number`) que `ContactDto` en
`prueba-tecnica-fronted-atl/src/app/core/repositories/contact.dto.ts`. Los teléfonos se
guardan y devuelven **normalizados a 10 dígitos** (`Support\PhoneNumber::normalize()`,
réplica exacta de `normalizePhoneNumber` en el adapter del frontend), que es justo el
formato que espera `PhoneFormatPipe` del lado Angular.

Son dos ejercicios independientes — este repo no depende del otro ni lo modifica — pero
al hablar el mismo idioma, conectar el Angular a esta API real es cambiar una línea en
`environment.ts` (`contactsApiUrl`), no reescribir el adapter. El "cómo conectarlos" está
documentado en el `README.md`.

**Alternativa descartada:** un envelope más "de libro" (`{"data": [...], "meta": {...}}`).
Es más genérico, pero pierde la compatibilidad directa con el frontend sin ganar nada que
el ejercicio pida.

---

## Errores en forma uniforme, con `errors` por campo en el 422

Todas las respuestas de error comparten `{"message": "..."}`, y el `422` añade
`"errors": {"campo": ["mensaje", ...]}` — la forma que usan Laravel y la mayoría de APIs
REST en PHP, elegida a propósito para que el resultado sea reconocible aunque el
enunciado pida explícitamente no usar un framework. `phones.0.number` como clave
(en vez de un array anidado) permite señalar el teléfono exacto que falló sin inventar un
formato de error propio.

---

## Sin contenedor de inyección de dependencias

`public/index.php` construye las ~6 dependencias de la API a mano (`Connection` →
`PdoContactRepository` → `ContactValidator`/`ContactService` → `ContactController` →
`Router`). Para dos entidades y cuatro endpoints, un contenedor de DI (o un micro-framework
de rutas) sería ceremonia sin beneficio real (YAGNI): el grafo de dependencias es lineal y
cabe en 15 líneas legibles.

---

## Tests: repositorio en memoria para Unit, SQLite real para Integration/Feature

`tests/Support/InMemoryContactRepository.php` implementa `ContactRepositoryInterface` sin
tocar PDO, así `ContactValidatorTest` (Unit) prueba las reglas de negocio sin pagar el
costo de una base de datos. `tests/Integration/PdoContactRepositoryTest.php` sí usa SQLite
en memoria (`sqlite::memory:`) para probar el SQL real: transacciones, borrado en cascada,
unicidad de email. `tests/Feature/ContactApiTest.php` arma el mismo stack que
`public/index.php` (incluida `PdoContactRepository`) y pasa un `Request` directamente al
`Kernel`, sin levantar un servidor HTTP — rápido y determinista, pero ejercitando la
aplicación completa de punta a punta.
