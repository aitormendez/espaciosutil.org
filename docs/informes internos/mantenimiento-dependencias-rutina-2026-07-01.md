# Mantenimiento de dependencias - rutina 2026-07-01

## Objetivo

Ejecucion asistida de mantenimiento ordinario de dependencias de espaciosutil.org, con actualizaciones patch/minor de bajo riesgo, auditorias, verificacion de build y preparacion para integracion en `main`.

## Rama y worktree

- Checkout principal: `/Volumes/D/Desarrollo/Sites/espaciosutil.org`
- Estado inicial del checkout principal: limpio
- `main` local inicial: `3b55283`
- `origin/main` inicial: `fe75c60`
- Worktree usado: `/Users/aitor/.config/superpowers/worktrees/espaciosutil.org/chore-deps-routine-2026-07`
- Rama temporal: `chore/deps-routine-2026-07`, creada desde `origin/main`

## Comandos ejecutados

- `git fetch origin --prune`
- `git status --short`
- `git worktree add -b chore/deps-routine-2026-07 /Users/aitor/.config/superpowers/worktrees/espaciosutil.org/chore-deps-routine-2026-07 origin/main`
- `composer install --no-interaction` en `site`
- `composer install --no-interaction` en `site/web/app/themes/sage`
- `npm ci` en `site/web/app/themes/sage`
- `composer outdated --direct` en `site`
- `composer audit` en `site`
- `composer outdated --direct` en `site/web/app/themes/sage`
- `composer audit` en `site/web/app/themes/sage`
- `npm outdated` en `site/web/app/themes/sage`
- `npm audit` en `site/web/app/themes/sage`
- `composer update blade-ui-kit/blade-icons laravel/pint roave/security-advisories wp-plugin/post-types-order wp-plugin/query-monitor wpengine/advanced-custom-fields-pro --with-dependencies --minimal-changes --no-interaction` en `site`
- `composer update laravel/pint guzzlehttp/guzzle guzzlehttp/psr7 --with-dependencies --minimal-changes --no-interaction` en `site/web/app/themes/sage`
- `npm audit fix --package-lock-only` en `site/web/app/themes/sage`
- `npm update gsap hls.js tocbot --package-lock-only` en `site/web/app/themes/sage`
- Verificacion final:
  - `composer install --no-interaction` en `site`
  - `composer audit` en `site`
  - `composer run lint` en `site`
  - `composer install --no-interaction` en `site/web/app/themes/sage`
  - `composer audit` en `site/web/app/themes/sage`
  - `npm ci` en `site/web/app/themes/sage`
  - `npm audit` en `site/web/app/themes/sage`
  - `npm run build` en `site/web/app/themes/sage`

## Paquetes actualizados

### Bedrock (`site/composer.lock`)

- `blade-ui-kit/blade-icons`: `1.10.0` -> `1.10.1`
- `laravel/pint`: `v1.29.1` -> `v1.29.3`
- `roave/security-advisories`: `dev-latest e17fcdc` -> `dev-latest 9eb7881`
- `wp-plugin/post-types-order`: `2.4.7` -> `2.4.8`
- `wp-plugin/query-monitor`: `4.0.6` -> `4.0.7`
- `wpengine/advanced-custom-fields-pro`: `6.8.4` -> `6.8.5`

### Sage Composer (`site/web/app/themes/sage/composer.lock`)

- `guzzlehttp/guzzle`: `7.10.0` -> `7.13.1`
- `guzzlehttp/promises`: `2.3.0` -> `2.5.0`
- `guzzlehttp/psr7`: `2.8.0` -> `2.12.3`
- `laravel/pint`: `v1.29.1` -> `v1.29.3`

### Sage NPM (`site/web/app/themes/sage/package-lock.json`)

- `gsap`: `3.13.0` -> `3.15.0`
- `hls.js`: `1.6.7` -> `1.6.16`
- `tar`: `7.5.15` -> `7.5.19`
- `tocbot`: `4.36.4` -> `4.36.8`
- `vite`: `6.4.2` -> `6.4.3`

## Paquetes omitidos

- `roots/wordpress` `6.9.4` -> `7.0`: omitido por salto major de WordPress core.
- `roots/acorn` `5.0.6` -> `6.2.0`: omitido por salto major de Acorn.
- `@roots/vite-plugin` `1.2.4` -> `1.3.1` / `2.2.0`: omitido por toolchain sensible; no requerido para corregir auditoria.
- `@tailwindcss/vite` `4.1.11` -> `4.3.2` y `tailwindcss` `4.1.11` -> `4.3.2`: omitidos por toolchain sensible.
- `@react-three/drei`, `@react-three/fiber`, `react`, `react-dom`, `swiper`, `@vidstack/react`: omitidos por frontend/runtime sensible.
- `@types/three`, `three`, `tsparticles`, `leva`, `infinite-scroll`, `laravel-vite-plugin`: omitidos porque el `wanted` no exige cambio o porque el `latest` implicaba major/paquete sensible.

## Verificaciones

- `composer install --no-interaction` en `site`: correcto.
- `composer audit` en `site`: correcto, sin advisories.
- `composer run lint` en `site`: correcto, `pint --test` paso.
- `composer install --no-interaction` en Sage: correcto. Se mantiene advertencia PSR-4 preexistente de `App\View\Composers\ContentSerie` en `app/View/Composers/contentSerie.php`.
- `composer audit` en Sage: correcto, sin advisories tras actualizar Guzzle/PSR-7.
- `npm ci` en Sage: correcto. Se mantienen advertencias peer/deprecated preexistentes alrededor de React 19, Leva/r3f-perf y tsParticles.
- `npm audit` en Sage: correcto, `found 0 vulnerabilities`.
- `npm run build` en Sage: correcto. Vite mostro advertencias conocidas de `use client` de Vidstack y chunk grande `vendor-cosmos`, sin fallo.

## Commit

- Commit creado en la rama temporal `chore/deps-routine-2026-07` con asunto `chore(deps): actualiza locks composer y npm de julio`.

## Integracion, deploy y smoke checks

- Integracion en `main`: bloqueada.
- Motivo: el checkout principal esta limpio, pero `main` local esta `ahead 3` respecto a `origin/main`. Los commits locales no publicados son:
  - `3b55283 chore(trellis): ajusta reglas de seguridad ssh`
  - `9763bda docs(contexto): documenta monitorizacion operativa externa`
  - `b5b7315 fix(atlas): elimina referencia a beta cerrada en acceso cde`
- Decision tomada: no se hizo push ni deploy para no publicar commits ajenos a esta rutina junto con el mantenimiento de dependencias.
- Push a `origin/main`: no ejecutado.
- Deploy produccion: no ejecutado.
- Smoke checks: no ejecutados.
- Rollback: no ejecutado.

## Estado final de produccion

Sin cambios. Produccion no fue desplegada en esta ejecucion.

## Follow-ups tecnicos

- Desbloquear la integracion decidiendo que hacer con los 3 commits locales de `main`: publicarlos, moverlos a otra rama o realinear `main` con `origin/main` mediante una accion explicita del owner del repositorio.
- Evaluar en issue separado los majors sensibles: WordPress `7.0`, Acorn `6.x`, toolchain Vite/Tailwind/Roots y runtime React/Three/Swiper/Vidstack.
- Corregir la advertencia PSR-4 preexistente de `App\View\Composers\ContentSerie` cuando se trabaje en deuda tecnica del tema.
