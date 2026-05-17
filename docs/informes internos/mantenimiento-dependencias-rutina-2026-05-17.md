# Mantenimiento de dependencias - 2026-05-17

## Objetivo

Ejecucion asistida de la rutina ordinaria de mantenimiento de dependencias de `espaciosutil.org`, limitada a lockfiles/manifiestos permitidos y sin saltos major automaticos.

## Rama y worktree

- Worktree: `/Users/aitor/.config/superpowers/worktrees/espaciosutil.org/chore-deps-routine-2026-05-2`
- Rama: `chore/deps-routine-2026-05-2`
- Base: `origin/main` en `9c79285 fix(cde): fija url solicitada de leccion gratuita`
- Checkout principal: sucio antes de empezar; no se uso para actualizar dependencias.
- Worktree mensual previo `chore/deps-routine-2026-05`: no reutilizado porque estaba sucio y desalineado de `origin/main`.

## Cambios aplicados

### Bedrock (`site/composer.lock`)

- `blade-ui-kit/blade-icons`: `1.9.0` -> `1.10.0`
- `codeat3/blade-coolicons`: `1.6.0` -> `1.7.0`
- `laravel/pint`: `v1.29.0` -> `v1.29.1`
- `log1x/acf-composer`: `v3.4.4` -> `v3.4.6`
- `roave/security-advisories`: `dev-latest 0d2dce3` -> `dev-latest 80f4d3d`
- `secondnetwork/blade-tabler-icons`: `3.40.0` -> `3.44.0`
- `wp-plugin/favicon-by-realfavicongenerator`: `1.3.46` -> `1.3.48`
- `wpengine/advanced-custom-fields-pro`: `6.7.1` -> `6.8.1`

### Tema Sage (`site/web/app/themes/sage/composer.lock`)

- `laravel/pint`: `v1.27.1` -> `v1.29.1`
- `roots/acorn`: omitido; disponible `5.0.6` -> `6.1.0`, salto major y paquete sensible.

### NPM (`site/web/app/themes/sage/package-lock.json`)

Se aplico `npm audit fix --package-lock-only` sin `--force`. El lock quedo con correcciones transitivas dentro de rango, incluyendo:

- `vite`: `6.4.2`
- `rollup`: `4.60.4`
- `postcss`: `8.5.14`
- `picomatch`: `4.0.4` y `2.3.2`
- `fast-uri`: `3.1.2`
- `tar`: `7.5.15`

No se aplico `npm audit fix --force`.

## Paquetes omitidos

- `swiper`: `11.2.10` -> `12.1.4` requerido por `npm audit fix --force`; omitido por salto major y paquete sensible.
- `roots/acorn`: `5.0.6` -> `6.1.0`; omitido por salto major y paquete sensible.
- Actualizaciones NPM `wanted` no ligadas a seguridad: se probo `npm update --package-lock-only`, pero dejo el lock no aceptable para `npm ci` por dependencia opcional ausente. Se descarto esa ampliacion y se mantuvo solo el arreglo de auditoria sin major.

## Verificaciones

- `cd site && composer install --no-interaction`: pasa.
- `cd site && composer audit`: pasa, sin advisories.
- `cd site && composer run lint`: falla por deuda previa de Pint en ficheros ajenos al diff (`web/app/languages/*`, ficheros PHP del tema, mu-plugins y scripts). No bloqueante segun la regla del issue.
- `cd site/web/app/themes/sage && composer install --no-interaction`: pasa. Composer informa avisos PSR-4 preexistentes en `contentSerie.php` y `Comments copy.php`.
- `cd site/web/app/themes/sage && composer audit`: pasa, sin advisories.
- `cd site/web/app/themes/sage && npm ci --no-audit`: pasa. Se uso para separar la instalacion limpia del gate de auditoria porque `npm audit` queda con el major omitido de Swiper.
- `cd site/web/app/themes/sage && npm audit`: queda solo `swiper` critico, corregible unicamente con `npm audit fix --force` y salto major a `12.1.4`.
- `cd site/web/app/themes/sage && npm run build`: pasa. Vite emite avisos existentes de directivas `"use client"` en Vidstack y chunks mayores de 500 kB.

## Diff final

El diff queda limitado al alcance ordinario:

- `site/composer.lock`
- `site/web/app/themes/sage/composer.lock`
- `site/web/app/themes/sage/package-lock.json`
- `docs/informes internos/mantenimiento-dependencias-rutina-2026-05-17.md`

## Commit, integracion y despliegue

- Commit creado: commit final de la rama `chore/deps-routine-2026-05-2` con asunto `chore(deps): actualiza lockfiles composer npm y auditoria sage`.
- Integracion en `main`: bloqueada por el estado del checkout principal. `main` esta sucio con cambios ajenos y ademas desalineado de `origin/main`; no se puede actualizar/integrar desde el checkout principal sin riesgo de pisar trabajo ajeno.
- Push a `main`: no ejecutado.
- Deploy produccion: no ejecutado porque la regla del issue exige desplegar desde `main` tras publicar `main`.
- Rollback: no aplica; no hubo deploy.

## Smoke checks

No ejecutados porque no hubo deploy.

## Estado final de produccion

Sin cambios desplegados desde esta rutina.

## Follow-ups tecnicos

- Resolver o decidir el salto major de `swiper` a `12.x`; actualmente queda como vulnerabilidad critica omitida por regla de seguridad del mantenimiento ordinario.
- Planificar `roots/acorn` `6.x` como cambio estructural separado.
- Limpiar deuda previa de Pint y avisos PSR-4 del tema para que futuras rutinas puedan diferenciar mejor fallos nuevos de deuda existente.
- Desbloquear el checkout principal `main`: limpiar o aparcar cambios ajenos y alinear `main` con `origin/main` antes de reintentar integracion y deploy.
