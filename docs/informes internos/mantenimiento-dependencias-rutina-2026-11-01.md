# Mantenimiento de dependencias — 2026-11-01

## Objetivo y estado

Ejecutar la rutina ordinaria de actualización de dependencias de espaciosutil.org. **Detenida en verificación.** No se creó commit, no se integró en `main`, no hubo push ni despliegue. Producción permanece en su estado anterior a esta ejecución; no se necesitó rollback.

## Preparación

- Checkout principal: `/Volumes/D/Desarrollo/Sites/espaciosutil.org`, rama `main`, con modificaciones y archivos sin seguimiento ajenos a esta rutina al inicio. No se modificaron sus archivos de código ni lockfiles.
- `git fetch origin`: correcto.
- Worktree limpio creado desde `origin/main` (`6ccf94d`): `.worktrees/chore-deps-routine-2026-11`, rama `chore/deps-routine-2026-11`.
- Todos los comandos de inventario, actualización y verificación se ejecutaron en ese worktree. Los tres lockfiles modificados permanecen allí sin commit.

## Inventario y clasificación

Se ejecutaron `composer outdated --direct` y `composer audit` en `site` y en Sage; inicialmente informaron que no había paquetes instalados, por lo que se ejecutó `composer install --no-interaction` en ambos y se repitió el inventario. También se ejecutaron `npm outdated` y `npm audit` en Sage.

| Clase | Paquetes | Decisión |
| --- | --- | --- |
| Ordinaria patch/minor | `roave/security-advisories`, `secondnetwork/blade-tabler-icons`, `vlucas/phpdotenv`, `wp-plugin/autodescription`, `wp-plugin/favicon-by-realfavicongenerator`, `wp-plugin/post-types-order`, `wp-plugin/query-monitor` | Actualizados en `site/composer.lock` con `composer update ... --with-all-dependencies --minimal-changes`. |
| Seguridad aplicable | `symfony/http-foundation` 7.4.13→7.4.22 y `symfony/mime` 7.4.13→7.4.22 | Actualizados en el lockfile de Sage; `composer audit` posterior: cero avisos. |
| Seguridad aplicable, verificación fallida | `vite` 6.4.3→6.4.5 y `source-map-js` 1.2.1→1.2.2 | Actualizados en `package-lock.json`; `npm audit` posterior: cero avisos, pero `npm ci` rechaza el lockfile. No son entregables integrados. |
| WordPress core patch | `roots/wordpress` 7.1.2→7.1.4 disponible | Se solicitó en Composer, pero no cambió el lockfile porque `composer.json` fija exactamente 7.1.2. Se omitió modificar la restricción del core en esta ejecución. |
| Major/estructural | `wp-plugin/taxonomy-terms-order` 1.9.9.1→2.1; `roots/acorn` 5.0.6→6.3.0; `infinite-scroll` 4→5; `leva` 0.9→0.10; `swiper` 12→14; `tsparticles` 2→4 | Omitidos por salto major o cambio estructural/sensible. |
| Frontend/runtime sensible | `@types/three` 0.155→0.187 y `three` 0.159→0.187 fuera de la restricción actual; otros cambios de React, React Three, Vidstack y Swiper | No se ampliaron restricciones. La incompatibilidad de pares de `three`/`postprocessing` impidió la actualización general de NPM sin `--legacy-peer-deps`. |

## Comandos de actualización

- `composer update roave/security-advisories roots/wordpress secondnetwork/blade-tabler-icons vlucas/phpdotenv wp-plugin/autodescription wp-plugin/favicon-by-realfavicongenerator wp-plugin/post-types-order wp-plugin/query-monitor --with-all-dependencies --minimal-changes --no-interaction --no-progress` en `site`: correcto; siete paquetes cambiados.
- `composer update symfony/http-foundation symfony/mime --with-all-dependencies --minimal-changes --no-interaction --no-progress` en Sage: correcto; dos paquetes cambiados.
- `npm update --package-lock-only --ignore-scripts --no-audit` en Sage: falló `ERESOLVE` por el conflicto entre `three@0.159.0` y `postprocessing@6.39.5`.
- `npm update fast-uri source-map-js vite --package-lock-only --legacy-peer-deps --ignore-scripts --no-audit`: generó un lockfile con `vite` y `source-map-js` corregidos, pero `npm ci` posterior lo rechazó por entradas faltantes. No se usó `--force` ni se cambió la restricción de `three`.

## Verificación y bloqueo

| Comando | Resultado |
| --- | --- |
| `site: composer install --no-interaction` | Correcto. |
| `site: composer audit` | Correcto; cero avisos. |
| `site: composer run lint` | **Fallo** de Pint en archivos PHP existentes fuera del alcance de dependencias, entre ellos `espaciosutil-cde-practicas.php` y `cde-presentations/*`. El diff de la rutina solo contiene lockfiles. |
| `Sage: composer install --no-interaction` | Correcto. |
| `Sage: composer audit` | Correcto; cero avisos. |
| `Sage: npm ci` | **Fallo `EUSAGE`**: el lockfile regenerado carece de paquetes requeridos, entre ellos `@types/react`, `webpack` y sus dependencias. |
| `Sage: npm audit` | Correcto; cero avisos en el lockfile candidato. |
| `Sage: npm run build` | No ejecutado: la instalación limpia falló y la instrucción de la tarea obliga a detenerse. |
| `git diff --check` | Correcto. Diff limitado a los tres lockfiles permitidos. |

## Integración y producción

No se creó commit ni se actualizó `main` por los fallos de verificación. No hubo push, despliegue ni smoke checks posteriores al despliegue. No hubo rollback. El estado final de producción no cambió por esta ejecución.

## Siguiente acción técnica

Resolver la incompatibilidad de pares NPM y regenerar un lockfile que pase `npm ci` sin alterar versiones principales o paquetes sensibles; corregir por separado el lint existente del código PHP o definir una verificación acotada aprobada para mantenimiento de lockfiles. Repetir la rutina completa desde un worktree alineado con `origin/main` antes de cualquier commit o despliegue. Los cambios candidatos de este worktree no deben tratarse como verificados.
