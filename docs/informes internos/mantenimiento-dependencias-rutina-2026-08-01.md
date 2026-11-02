# Mantenimiento ordinario de dependencias — 2026-08-01

## Objetivo y resultado

Ejecutar la rutina mensual asistida de mantenimiento ordinario de dependencias de `espaciosutil.org`, limitada a actualizaciones patch/minor y correcciones de seguridad compatibles con los constraints existentes.

Resultado: **detenida por bloqueo de verificación**. `npm ci` rechazó el `package-lock.json` generado porque falta la entrada `@emnapi/wasi-threads@1.2.3`. Conforme a la política de la rutina, no se creó commit, no se integró en `main` y no se desplegó producción.

## Preparación Git

- Estado inicial del checkout principal: limpio.
- `main` y `origin/main` iniciales: `ea40ca6f84f33b90922f643f56fee4ae7d208a3a`.
- Rama temporal: `chore/deps-routine-2026-08`.
- Worktree aislado: `/private/var/folders/by/6ff6_t2d0nj57yj54y8jj16r0000gn/T/paperclip-run-esp-1577-811a4f23-6fe-lecvV7/chore-deps-routine-2026-08`.
- Commit creado: ninguno.
- El diff del worktree quedó limitado a:
  - `site/composer.lock`
  - `site/web/app/themes/sage/composer.lock`
  - `site/web/app/themes/sage/package-lock.json`
- `git diff --check`: sin errores.

## Inventario y clasificación

### Aplicables en Composer del sitio

- `laravel/pint`: 1.29.3 → 1.30.2 (minor).
- `roave/security-advisories`: `9eb7881` → `3c9ad68` (actualización ordinaria del lock).
- `secondnetwork/blade-tabler-icons`: 3.44.0 → 3.46.0 (minor).
- `vlucas/phpdotenv`: 5.6.3 → 5.6.4 (patch).
- `wp-plugin/favicon-by-realfavicongenerator`: 1.3.48 → 1.3.50 (patch).
- `wpengine/advanced-custom-fields-pro`: 6.8.5 → 6.8.6 (patch, sin cambio de constraint ni salto estructural).
- Auditoría inicial: sin avisos de seguridad.

### Aplicables en Composer del tema

- `laravel/pint`: 1.29.3 → 1.30.2 (minor).
- `guzzlehttp/guzzle`: 7.13.1 → 7.15.2 (minor de seguridad).
- Dependencias mínimas asociadas a Guzzle:
  - `guzzlehttp/promises`: 2.5.0 → 2.5.1.
  - `guzzlehttp/psr7`: 2.12.3 → 2.13.0.
- La actualización de Guzzle resolvía cuatro avisos de severidad media presentes en versiones anteriores a 7.15.1/7.14.2.

### Aplicables en NPM mediante lockfile-only

- `@react-three/drei`: 10.4.4 → 10.7.7.
- `@react-three/fiber`: 9.2.0 → 9.7.0.
- `@roots/vite-plugin`: 1.2.4 → 1.3.1.
- `@tailwindcss/vite`: 4.1.11 → 4.3.3.
- `@vidstack/react`: 1.12.13 → 1.15.6.
- `react` y `react-dom`: 19.1.0 → 19.2.8.
- `swiper`: 12.1.4 → 12.2.0.
- `tailwindcss`: 4.1.11 → 4.3.3.
- Correcciones de seguridad transitivas:
  - `fast-uri`: 3.1.2 → 3.1.5.
  - `postcss`: 8.5.14 → 8.5.25.
  - `tar` vulnerable dejó de aparecer en el árbol resuelto.
- `npm audit` tras actualizar el lockfile informó 0 vulnerabilidades, antes de la reinstalación fallida.

### Omitidas

- `roots/wordpress`: 6.9.4 → 7.0.2; salto major de WordPress crítico.
- `roots/acorn`: 5.0.6 → 6.2.0; salto major sensible.
- `@roots/vite-plugin` 2.x, Vite 8.x, `laravel-vite-plugin` 3.x y Tailwind fuera de constraints: toolchain major omitida.
- `infinite-scroll` 5.x, `leva` 0.10.x, Three 0.185.x, tsParticles 4.x y Swiper 14.x: frontend/runtime major o estructural omitido.
- No se modificaron constraints ni manifiestos `composer.json`/`package.json`.

## Comandos ejecutados

Preparación e inventario:

```text
git fetch origin
git status --short
git worktree list --porcelain
git worktree add <worktree> -b chore/deps-routine-2026-08 origin/main
cd site && composer install --no-interaction
cd site && composer outdated --direct
cd site && composer audit
cd site/web/app/themes/sage && composer install --no-interaction
cd site/web/app/themes/sage && composer outdated --direct
cd site/web/app/themes/sage && composer audit
cd site/web/app/themes/sage && npm ci
cd site/web/app/themes/sage && npm outdated
cd site/web/app/themes/sage && npm audit
```

Actualización:

```text
cd site && composer update laravel/pint roave/security-advisories secondnetwork/blade-tabler-icons vlucas/phpdotenv wp-plugin/favicon-by-realfavicongenerator wpengine/advanced-custom-fields-pro --with-dependencies --minimal-changes --no-interaction
cd site/web/app/themes/sage && composer update laravel/pint guzzlehttp/guzzle --with-dependencies --minimal-changes --no-interaction
cd site/web/app/themes/sage && npm update --package-lock-only
```

Verificación:

```text
cd site && composer install --no-interaction
cd site && composer audit
cd site && composer run lint
cd site/web/app/themes/sage && composer install --no-interaction
cd site/web/app/themes/sage && composer audit
cd site/web/app/themes/sage && npm ci
```

No se ejecutaron `npm audit` ni `npm run build` después del `npm ci` de verificación porque la política exige detenerse en el primer fallo.

## Verificaciones

| Verificación | Resultado |
|---|---|
| `site/composer install --no-interaction` | Correcta; lock instalable. |
| `site/composer audit` | Correcta; 0 avisos. |
| `site/composer run lint` | Correcta; Pint pasó. |
| `sage/composer install --no-interaction` | Correcta; lock instalable. Se mantuvo el aviso PSR-4 preexistente de `ContentSerie`. |
| `sage/composer audit` | Correcta; 0 avisos. |
| `sage/npm ci` | **Fallida**: falta `@emnapi/wasi-threads@1.2.3` en `package-lock.json`. |
| `sage/npm audit` final | No ejecutada tras el fallo. |
| `sage/npm run build` | No ejecutada tras el fallo. |

## Bloqueo y siguiente acción

El lockfile producido por `npm update --package-lock-only` no es autoconsistente para `npm ci`. La siguiente ejecución técnica debe regenerar o corregir el lockfile con una operación NPM reproducible, confirmar que la entrada opcional de `@emnapi/wasi-threads` queda registrada y repetir toda la matriz de verificación desde cero. No debe reutilizarse ni publicarse el diff actual sin esa verificación.

## Integración, despliegue y producción

- Integración en `main`: no ejecutada.
- Push de `main`: no ejecutado.
- Deploy Trellis a producción: no ejecutado.
- Rollback: no necesario.
- Smoke checks: no ejecutados, al no haber deploy.
- Estado final de producción: **sin cambios respecto al inicio de la rutina**.

## Follow-ups técnicos fuera del mantenimiento ordinario

- Evaluar WordPress 7 en una tarea específica con compatibilidad de plugins y rollback.
- Evaluar Acorn 6 y majors de Vite/Roots/Tailwind en una migración de toolchain independiente.
- Tratar los avisos de peer dependencies React/Three y el aviso PSR-4 de `ContentSerie` como deuda separada; no forman parte de este mantenimiento ordinario.
