# 01 - FLUJO GIT Y GITHUB

## 📖 Guía completa del workflow de Git Flow

Este documento explica el flujo de trabajo con Git y GitHub para el proyecto **API de
contactos** (prueba técnica backend). Adaptado del mismo esquema usado en
`project-mobile-ionic` y `prueba-tecnica-fronted-atl`, para que los tres repos hablen el
mismo idioma de Git.

---

## 🌳 Estructura de ramas

```
main (producción / entrega final - protegida)
  ↑
  │ (PR al final de cada fase)
  │
develop (integración - default)
  ↑
  │ (PRs de cada tarea)
  │
feature/<descripción> (tareas individuales)
```

- **`main`** — solo código estable y probado; protegida, solo acepta PRs de `develop`.
- **`develop`** — integración de todas las tareas; rama por defecto del repositorio; base
  para crear ramas `feature/*`; siempre debe estar funcional (`composer lint && composer
  test` en verde).
- **`feature/<descripción>`** — una rama por tarea; se crea desde `develop`; se fusiona de
  vuelta vía PR; se elimina después del merge.

---

## ☁️ Adaptación a Claude Code on the web

Este repositorio se desarrolla con **Claude Code on the web**, donde cada sesión trabaja
sobre una **rama de sesión asignada** (p. ej. `claude/rest-api-contacts-*`) y, por
seguridad, **solo puede hacer push a esa rama**. Por eso la convención práctica es:

| Quién | Hace |
|-------|------|
| **Claude (sesión web)** | Desarrolla la tarea completa en su **rama de sesión**: crea el issue, commitea con Conventional Commits, abre el PR rama→`develop` y lo mergea. Llega solo hasta `develop`. |
| **Tú (mantenedor)** | Revisas `develop`, y cuando quieras llevar el trabajo a `main` (entrega final), lo pides explícitamente — Claude abre ese PR y espera tu aprobación para mergearlo. |

> **Regla clave:** el único paso que requiere pedirlo explícitamente es el PR
> `develop → main`. Todo lo anterior (issue → commits → PR → merge a `develop`) ocurre
> sin que haga falta pedirlo cada vez.

---

## 🔄 Flujo de trabajo por tarea

```
1. Crear issue describiendo la tarea
2. Desarrollar en la rama de sesión, commiteando por capa/pieza lógica
3. composer lint && composer test antes de dar por cerrada la tarea
4. Push a la rama de sesión
5. Crear PR rama de sesión → develop (Closes #N)
6. Merge del PR a develop, cerrar issue
7. Avisar que develop está listo; PR develop → main solo si se pide explícitamente
```

---

## 📝 Convenciones de commits

**Conventional Commits:**

```
feat: nueva funcionalidad
fix: corrección de bug
docs: cambios en documentación
refactor: refactorización sin cambio de comportamiento
test: agregar o modificar tests
chore: tareas de mantenimiento (tooling, dependencias)
ci: cambios en integración continua

Ejemplos (contexto de esta API):
feat: add sqlite persistence layer with pdo
feat: add validation rules for contacts and phones
fix: normalize phone country code before storing
test: add integration tests for pdo contact repository
docs: add api reference and postman collection
```

---

## 🛠️ Comandos de referencia rápida

### Git básico

```bash
git status
git branch -a
git checkout -b feature/nombre-descriptivo
git log --oneline --graph --all
git fetch origin
git pull origin develop
```

### Composer / PHP

```bash
composer install       # instalar phpunit (dev); la API no lo necesita para arrancar
composer lint           # php -l sobre src/, public/, bin/
composer test           # PHPUnit
composer serve          # servidor embebido → http://localhost:8000
composer migrate -- --fresh --seed
```

---

## ✅ Buenas prácticas

**Hacer:**
- Commits frecuentes y descriptivos, uno por capa/pieza lógica cuando tenga sentido.
- `composer lint && composer test` antes de cada push.
- Mantener `develop` siempre funcional.
- Cerrar issues al completar tareas (`Closes #N` en el PR).

**No hacer:**
- Trabajar directamente en `main`.
- Commits con mensajes vagos ("fix", "wip", "cambios").
- Push de código que no pasa lint o tests.
- Dejar credenciales o rutas absolutas del entorno en el código versionado.

---

## 🔐 Seguridad

### `.gitignore` esencial (PHP / Composer)

```gitignore
/vendor/
/database/*.sqlite
/storage/logs/*.log
.env
.env.local
```

### ⚠️ Nunca commitear

- Credenciales de base de datos reales (host, usuario, contraseña de producción).
- Archivos `.env` con secretos.
- El archivo `.sqlite` generado en desarrollo (se regenera con `bin/migrate.php`).

---

## 📞 Ayuda adicional

- **Git Docs**: https://git-scm.com/doc
- **Conventional Commits**: https://www.conventionalcommits.org/
- **Composer**: https://getcomposer.org/
