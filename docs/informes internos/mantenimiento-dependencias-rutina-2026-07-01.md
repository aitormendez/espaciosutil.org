# Mantenimiento de dependencias - rutina 2026-07-01

## Objetivo

Ejecucion asistida de mantenimiento ordinario de dependencias de espaciosutil.org, con actualizaciones patch/minor de bajo riesgo, auditorias, verificacion de build, integracion en `main`, despliegue de produccion y smoke checks.

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
- `git cherry-pick 6d97096` en checkout principal
- `git push origin main`
- `trellis deploy production espaciosutil.org` en `trellis`
- Smoke checks HTTP con `curl` sobre home, login/wp-admin, suscripcion, leccion CDE y assets principales del tema

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

## Commits

- Commit creado en la rama temporal `chore/deps-routine-2026-07` con asunto `chore(deps): actualiza locks composer y npm de julio`.
- Commit integrado en `main` mediante cherry-pick limpio: `164d617 chore(deps): actualiza locks composer y npm de julio`.
- La integracion se hizo despues de aceptar publicar los 3 commits locales que `main` tenia pendientes:
  - `3b55283 chore(trellis): ajusta reglas de seguridad ssh`
  - `9763bda docs(contexto): documenta monitorizacion operativa externa`
  - `b5b7315 fix(atlas): elimina referencia a beta cerrada en acceso cde`

## Integracion, deploy y smoke checks

- Integracion en `main`: correcta.
- Push a `origin/main`: correcto, `fe75c60..164d617`.
- Deploy produccion: correcto, `main@164d617` desplegado como release `20260702115530`.
- Smoke checks:
  - Home publica `https://espaciosutil.org/`: `200`.
  - Login/wp-admin `https://espaciosutil.org/wp/wp-admin/`: `200`, redirige correctamente a `wp-login.php`.
  - Suscripcion `https://espaciosutil.org/suscripcion/`: `200`.
  - Leccion CDE publica `https://espaciosutil.org/lecciones-del-cde/seth/planteamiento-general-que-es-la-realidad/`: `200`, redirige a canonical `/lecciones-del-cde/seth-realidad-y-existencia/planteamiento-general-que-es-la-realidad/`.
  - CSS principal Sage `app-BXWJp7kO.css`: `200 text/css`.
  - JS principal Sage `app-jL6j0S5N.js`: `200 text/javascript`.
- Rollback: no ejecutado.

## Estado final de produccion

Produccion actualizada correctamente con release `20260702115530` y commit de sitio `main@164d617`. No hubo rollback.

## Follow-ups tecnicos

- Evaluar en issue separado los majors sensibles: WordPress `7.0`, Acorn `6.x`, toolchain Vite/Tailwind/Roots y runtime React/Three/Swiper/Vidstack.
- Corregir la advertencia PSR-4 preexistente de `App\View\Composers\ContentSerie` cuando se trabaje en deuda tecnica del tema.
- Retirar o proteger la salida de debug del hook de deploy que imprime estructura de `auth.json` de Composer, para evitar exposicion accidental en logs operativos.
