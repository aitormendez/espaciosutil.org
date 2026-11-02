# Mantenimiento ordinario de dependencias — 2026-09-01

## Objetivo y resultado

Ejecutar la rutina asistida de mantenimiento ordinario de dependencias de `espaciosutil.org`: aplicar actualizaciones patch/minor y correcciones de seguridad de bajo riesgo, verificar los lockfiles, integrar en `main`, desplegar producción y realizar smoke checks.

Resultado: **detenida por bloqueo de permisos al publicar `main`**. Las actualizaciones se aplicaron y toda la matriz local de verificación pasó. Se creó el commit `a935def2058dc0d5dea710e31051f867fe0e7fe8`, pero el control de ejecución externo rechazó `git push origin HEAD:main`. Conforme a la política de la rutina, no se desplegó desde la rama temporal y producción quedó sin cambios.

## Preparación Git

- Estado inicial del checkout principal: sucio por un archivo ajeno sin seguimiento, `docs/informes internos/mantenimiento-dependencias-rutina-2026-08-01.md`.
- `main` y `origin/main` iniciales: `dafc670a801a0d7419f13ef7793fdb44604332fb`.
- El checkout principal no se usó para actualizar dependencias ni integrar cambios.
- Rama temporal: `chore/deps-routine-2026-09`.
- Worktree aislado: `/private/var/folders/by/6ff6_t2d0nj57yj54y8jj16r0000gn/T/paperclip-run-esp-1664-15e099d2-599-g2l73K/chore-deps-routine-2026-09`.
- Commit creado: `a935def2058dc0d5dea710e31051f867fe0e7fe8`.
- Asunto del commit: `chore(deps): actualiza mantenimiento ordinario de dependencias`.
- El asunto se amplió respecto al ejemplo de la rutina porque el hook `commit-msg` rechaza descripciones genéricas de cuatro palabras o menos. El commit pasó los hooks normales; no se omitió el control de vaults.
- Diff del commit limitado a:
  - `site/composer.lock`
  - `site/web/app/themes/sage/composer.lock`
  - `site/web/app/themes/sage/package-lock.json`
- `git diff --check`: correcto.

## Inventario y clasificación

### Composer del sitio: aplicadas

- `laravel/pint`: 1.29.3 → 1.30.5.
- `log1x/acf-composer`: 3.4.6 → 3.4.7.
- `roave/security-advisories`: `9eb7881` → `2f891ad`.
- `secondnetwork/blade-tabler-icons`: 3.44.0 → 3.46.0.
- `vlucas/phpdotenv`: 5.6.3 → 5.7.0.
- `wp-plugin/favicon-by-realfavicongenerator`: 1.3.48 → 1.3.50.
- `wp-plugin/html-forms`: 1.6.4 → 1.6.5.
- `wp-plugin/post-types-order`: 2.4.8 → 2.5.
- `wpengine/advanced-custom-fields-pro`: 6.8.5 → 6.8.9.
- Dependencias transitivas mínimas de `phpdotenv`:
  - `graham-campbell/result-type`: 1.1.4 → 1.2.0.
  - `phpoption/phpoption`: 1.9.5 → 1.10.0.
- Auditoría inicial y final: sin avisos de seguridad.

### Composer del tema: aplicadas

- `laravel/pint`: 1.29.3 → 1.30.5.
- `guzzlehttp/guzzle`: 7.13.1 → 7.15.5, corrección de seis avisos de seguridad (uno alto y cinco medios).
- Dependencias mínimas asociadas:
  - `guzzlehttp/promises`: 2.5.0 → 2.5.3.
  - `guzzlehttp/psr7`: 2.12.3 → 2.13.1.
- Auditoría final: sin avisos de seguridad.

### NPM: aplicadas en lockfile

- `hls.js`: 1.6.16 → 1.7.1, minor compatible.
- Correcciones de seguridad transitivas mediante `npm audit fix --package-lock-only`, sin `--force`:
  - `fast-uri`: 3.1.2 → 3.1.6.
  - `nanoid`: 3.3.11 → 3.3.18.
  - `postcss`: 8.5.14 → 8.5.26.
  - `tar`: 7.5.19 → 7.5.22.
- Auditoría inicial: cuatro vulnerabilidades altas.
- Auditoría final: cero vulnerabilidades.
- No se modificó `package.json`.

### Omitidas

- `roots/wordpress` 7.0.3 → 7.1: release mayor de WordPress core; requiere validación específica de compatibilidad y rollback.
- `roots/acorn` 5.0.6 → 6.2.0: salto major sensible.
- `wp-plugin/taxonomy-terms-order` 1.9.9.1 → 2.0: salto major.
- `@roots/vite-plugin` 1.3.x/2.x, Tailwind 4.3.x, Vite 8.x y `laravel-vite-plugin` 3.x: toolchain sensible o major.
- `@react-three/drei`, `@react-three/fiber`, `@react-three/postprocessing`, React/React DOM, Three, Swiper y Vidstack: frontend/runtime sensible.
- `infinite-scroll` 5.x, Leva 0.10.x y tsParticles 4.x: major o cambio estructural.
- No se aplicó `npm audit fix --force` ni se modificaron constraints.

## Comandos ejecutados

Preparación e inventario:

```text
git fetch origin
git status --short
git worktree list --porcelain
git worktree add -b chore/deps-routine-2026-09 <worktree> origin/main
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
cd site && composer update laravel/pint log1x/acf-composer roave/security-advisories secondnetwork/blade-tabler-icons vlucas/phpdotenv wp-plugin/favicon-by-realfavicongenerator wp-plugin/html-forms wp-plugin/post-types-order wpengine/advanced-custom-fields-pro --with-dependencies --minimal-changes --no-interaction
cd site/web/app/themes/sage && composer update laravel/pint guzzlehttp/guzzle --with-dependencies --minimal-changes --no-interaction
cd site/web/app/themes/sage && npm audit fix --package-lock-only
cd site/web/app/themes/sage && npm update hls.js --package-lock-only
```

Verificación:

```text
cd site && composer install --no-interaction
cd site && composer audit
cd site && composer run lint
cd site/web/app/themes/sage && composer install --no-interaction
cd site/web/app/themes/sage && composer audit
cd site/web/app/themes/sage && npm ci
cd site/web/app/themes/sage && npm audit
cd site/web/app/themes/sage && npm run build
git diff --check
```

Commit e integración intentada:

```text
git add site/composer.lock site/web/app/themes/sage/composer.lock site/web/app/themes/sage/package-lock.json
git commit -m "chore(deps): actualiza mantenimiento ordinario de dependencias" ...
git fetch origin
git push origin HEAD:main
```

## Verificaciones

| Verificación | Resultado |
|---|---|
| `site/composer install --no-interaction` | Correcta; lock instalable. |
| `site/composer audit` | Correcta; cero avisos. |
| `site/composer run lint` | Correcta; Pint pasó. |
| `sage/composer install --no-interaction` | Correcta; se mantiene el aviso PSR-4 preexistente de `ContentSerie`. |
| `sage/composer audit` | Correcta; cero avisos tras actualizar Guzzle. |
| `sage/npm ci` | Correcta; lockfile reproducible. Se mantienen avisos peer/deprecated preexistentes de React/Leva/r3f/tsParticles. |
| `sage/npm audit` | Correcta; cero vulnerabilidades. |
| `sage/npm run build` | Correcta; se mantienen avisos conocidos de `use client` de Vidstack y chunk grande `vendor-cosmos`. |
| Alcance y whitespace | Correctos; tres lockfiles y `git diff --check` sin errores. |

## Integración, despliegue y producción

- El segundo `git fetch origin` confirmó que `origin/main` seguía en `dafc670a801a0d7419f13ef7793fdb44604332fb`, padre directo del commit de mantenimiento.
- Integración en `main`: **bloqueada**. El control de permisos externo rechazó el push fast-forward normal `git push origin HEAD:main`; no se intentó force push ni ninguna vía alternativa.
- Push de `main`: no ejecutado por el control de permisos.
- Deploy Trellis a producción: no ejecutado, porque `main` no se publicó.
- Rollback: no necesario.
- Smoke checks: no ejecutados, al no haber deploy.
- Estado final de producción: **sin cambios respecto al inicio de la rutina**.

## Bloqueo y siguiente acción

Propietario del desbloqueo: operador/board con autoridad para aprobar el push directo fast-forward a `origin/main` en esta ejecución.

Acción exacta: autorizar `git push origin HEAD:main` desde `chore/deps-routine-2026-09`. Tras el push, continuar sin nueva revisión funcional con deploy Trellis desde `main`, smoke checks y actualización de este informe. Si el push encuentra divergencia o conflicto, detenerse sin forzar.

## Follow-ups técnicos fuera del mantenimiento ordinario

- Evaluar WordPress 7.1 en una tarea específica de core con matriz de compatibilidad y rollback.
- Evaluar Acorn 6 y majors de Vite/Roots/Tailwind en una migración separada de toolchain.
- Tratar los avisos peer de React/Leva/r3f, las dependencias obsoletas de tsParticles y el aviso PSR-4 de `ContentSerie` como deuda independiente.
