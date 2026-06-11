# Informe de mantenimiento de dependencias - 2026-06-11

## Objetivo

Ejecutar la rutina asistida de mantenimiento ordinario de dependencias de `espaciosutil.org`: aplicar actualizaciones patch/minor o de seguridad de bajo riesgo, verificar, commitear, integrar en `main`, desplegar produccion y dejar evidencia.

## Rama y worktree

- Checkout principal: `/Volumes/D/Desarrollo/Sites/espaciosutil.org`
- Estado inicial del checkout principal: sucio antes de empezar, con cambios ajenos en `docs/context/README.md` y `docs/context/monitorizacion-operativa.md`.
- Worktree usado: `/Users/aitor/.config/superpowers/worktrees/espaciosutil.org/chore-deps-routine-2026-06`
- Rama temporal: `chore/deps-routine-2026-06`
- Base: `origin/main` en `73eb06a` (`fix(redirects): crea redireccion corta cde`)

## Comandos ejecutados

- `git fetch origin`
- `git status --short`
- `git worktree list --porcelain`
- `git worktree add -b chore/deps-routine-2026-06 /Users/aitor/.config/superpowers/worktrees/espaciosutil.org/chore-deps-routine-2026-06 origin/main`
- `composer outdated --direct` en `site`
- `composer audit` en `site`
- `composer outdated --direct` en `site/web/app/themes/sage`
- `composer audit` en `site/web/app/themes/sage`
- `npm outdated` en `site/web/app/themes/sage`
- `npm audit` en `site/web/app/themes/sage`
- `composer install --no-interaction` en `site`
- `composer install --no-interaction` en `site/web/app/themes/sage`
- `npm ci` en `site/web/app/themes/sage`
- `composer update roave/security-advisories wp-plugin/post-types-order wp-plugin/taxonomy-terms-order wp-theme/twentytwentyfive wpengine/advanced-custom-fields-pro --with-dependencies --minimal-changes --no-interaction` en `site`
- `composer update symfony/http-foundation symfony/http-kernel symfony/mime symfony/polyfill-intl-idn symfony/routing --with-dependencies --minimal-changes --no-interaction` en `site/web/app/themes/sage`
- `npm update --package-lock-only` en `site/web/app/themes/sage`
- `npm install --package-lock-only` en `site/web/app/themes/sage`
- `git restore site/web/app/themes/sage/package-lock.json`
- `composer run lint` en `site`
- `php tests/campaign-redirects.php` en `site`
- `npm run build` en `site/web/app/themes/sage`
- `git push origin main`
- `trellis deploy production espaciosutil.org`
- `curl` smoke checks sobre home, login/wp-admin, suscripcion, CDE, una leccion critica y assets principales.

## Paquetes actualizados

### Bedrock (`site/composer.lock`)

- `roave/security-advisories`: `dev-latest 80f4d3d` -> `dev-latest e17fcdc`
- `wp-plugin/post-types-order`: `2.4.6` -> `2.4.7`
- `wp-plugin/taxonomy-terms-order`: `1.9.5` -> `1.9.9.1`
- `wp-theme/twentytwentyfive`: `1.4` -> `1.5`
- `wpengine/advanced-custom-fields-pro`: `6.8.1` -> `6.8.4`

### Tema Sage (`site/web/app/themes/sage/composer.lock`)

- `symfony/http-foundation`: `v7.4.6` -> `v7.4.13`
- `symfony/http-kernel`: `v7.4.6` -> `v7.4.13`
- `symfony/mime`: `v7.4.6` -> `v7.4.13`
- `symfony/polyfill-intl-idn`: `v1.33.0` -> `v1.38.1`
- `symfony/routing`: `v7.4.6` -> `v7.4.13`

### Formato auxiliar

- `site/web/app/mu-plugins/espaciosutil-campaign-redirects.php`
- `site/tests/campaign-redirects.php`

Se incorporo el formato Pint minimo ya validado en el blocker de lint para permitir que la verificacion obligatoria de la rutina pasara. No hubo cambio funcional de redirects.

## Paquetes omitidos y motivo

- `roots/wordpress` `6.9.4` -> `7.0`: omitido por major de WordPress core.
- `roots/acorn` `5.0.6` -> `6.2.0`: omitido por major sensible de Acorn.
- `site/web/app/themes/sage/package-lock.json`: se intento update lockfile-only para versiones `wanted` dentro de rango, pero `npm ci` fallo despues por lock inconsistente en dependencias opcionales/bundled de `@tailwindcss/oxide-wasm32-wasi` (`@emnapi/core`, `@emnapi/runtime`, `@emnapi/wasi-threads`). Se revirtio el cambio npm completo porque `npm audit` ya estaba limpio.
- `infinite-scroll` `4.0.1` -> `5.0.0`: omitido por major.
- `laravel-vite-plugin` `1.3.0` -> `3.1.0`: omitido por major/toolchain sensible.
- `leva` `0.9.36` -> `0.10.1`: omitido por minor sensible y sin necesidad de seguridad.
- `three` `0.159.0` -> `0.184.0`: omitido por runtime sensible.
- `tsparticles` `2.12.0` -> `4.1.3`: omitido por major.
- `@types/three` `0.155.1` -> `0.184.1`: omitido por major alineado con `three`.

## Verificaciones

- `cd site && composer install --no-interaction`: pasa.
- `cd site && composer audit`: pasa, sin advisories.
- `cd site && composer run lint`: pasa, `{"tool":"pint","result":"passed"}`.
- `cd site && php tests/campaign-redirects.php`: pasa, exit 0.
- `cd site/web/app/themes/sage && composer install --no-interaction`: pasa. Composer mantiene el aviso PSR-4 preexistente de `App\View\Composers\ContentSerie`.
- `cd site/web/app/themes/sage && composer audit`: pasa, sin advisories.
- `cd site/web/app/themes/sage && npm ci`: pasa con warnings peer/deprecated ya existentes.
- `cd site/web/app/themes/sage && npm audit`: pasa, `0 vulnerabilities`.
- `cd site/web/app/themes/sage && npm run build`: pasa. Vite mantiene warnings conocidos de directivas `"use client"` en Vidstack y chunks grandes.

## Diff final

Diff commiteado:

- `site/composer.lock`
- `site/web/app/themes/sage/composer.lock`
- `site/web/app/mu-plugins/espaciosutil-campaign-redirects.php`
- `site/tests/campaign-redirects.php`

El informe queda en el checkout principal como evidencia de ejecucion de la rutina.

## Commit, integracion y despliegue

- Commit de mantenimiento: `7fb9289 chore(deps): actualiza composer y lint de redirects`
- Integracion: fast-forward limpio de `main` desde `73eb06a` a `7fb9289`.
- Push: `origin/main` actualizado de `73eb06a` a `7fb9289`.
- Deploy produccion: ejecutado desde `main` con `cd trellis && trellis deploy production espaciosutil.org`.
- Resultado Trellis: OK, `main@7fb9289 deployed as release 20260611153009`.
- Rollback: no ejecutado; no fue necesario.

## Smoke checks

- `https://espaciosutil.org/`: HTTP 200.
- `https://espaciosutil.org/wp/wp-admin/`: HTTP 200 tras redireccion a login.
- `https://espaciosutil.org/suscripcion/`: HTTP 200.
- `https://espaciosutil.org/curso-de-desarrollo-espiritual/`: HTTP 200.
- `https://espaciosutil.org/lecciones-del-cde/seth-realidad-y-existencia/planteamiento-general-que-es-la-realidad/`: HTTP 200.
- Asset CSS principal `app-rxRKNocP.css`: HTTP 200.
- Asset JS principal `app-DZR3yPMM.js`: HTTP 200.

## Estado final de produccion

Produccion queda desplegada y operativa en la release `20260611153009`, generada desde `main@7fb9289`.

## Follow-ups tecnicos

- Planificar `roots/wordpress` `7.x` como upgrade major separado.
- Planificar `roots/acorn` `6.x` como cambio estructural separado.
- Evaluar por separado el update npm lockfile-only de Tailwind/Vite/React/3D cuando se pueda reproducir un lock compatible con `npm ci`.
- Revisar la deuda PSR-4 de `App\View\Composers\ContentSerie` en Sage.
