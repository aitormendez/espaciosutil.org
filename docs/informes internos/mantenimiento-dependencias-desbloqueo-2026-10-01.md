# Mantenimiento de dependencias — desbloqueo del 1 de octubre de 2026

## Resultado

El bloqueo original de formato está corregido. Las ocho verificaciones obligatorias pasan y los cambios están integrados y publicados en `main`. Producción no se ha modificado: el despliegue completo requiere ampliar el alcance a cambios previos de WordPress y CDE que no son mantenimiento ordinario.

## Diagnóstico reproducido

Sobre la base `7b190adddf7bce86e48401a7115eae10002c7f81`, Pint 1.30.5 y 1.32.1 producen resultados JSON idénticos: los mismos 32 archivos y reglas. Por tanto, el fallo era previo a la actualización del verificador. La instalación de Composer se realizó en un worktree nuevo, sin reutilizar dependencias del checkout principal.

Pint necesitó dos pasadas de corrección: tras expandir sentencias, la siguiente pasada normalizó argumentos multilínea y una coma final. Después de la segunda pasada, `--test` pasa con ambas versiones. No se cambiaron la configuración de Pint ni sus exclusiones.

Se compararon los árboles sintácticos de los 32 archivos antes y después con nikic/php-parser instalado únicamente en un directorio temporal. Son equivalentes al normalizar dos formas semánticamente iguales: visibilidad pública implícita/expresa de métodos y `else if`/`elseif`. No aparecieron otras diferencias. Se comprobaron además la sintaxis y las regresiones existentes.

## Commits publicados

- `5a9908c`: `style(php): normaliza el formato de CDE y sus pruebas`. Exclusivamente los 32 archivos PHP; no incluye dependencias.
- `1c79a6d`: `chore(deps): incorpora mantenimiento y correcciones de seguridad de octubre`. Exclusivamente los tres lockfiles autorizados.
- Rama de trabajo: `codex/esp-2007-mantenimiento`.
- Worktree: `/Users/aitor/.codex/worktrees/esp-2007-mantenimiento/espaciosutil.org`.
- Integración: push normal a `main`, avance lineal desde `7b190ad`, sin force push ni conflictos. Checkout principal actualizado por fast-forward, conservando sus archivos no seguidos.

## Verificación

- `composer install --no-interaction`, `composer audit` y `composer run lint` del sitio: PASS.
- `composer install --no-interaction` y `composer audit` del tema: PASS.
- `npm ci`, `npm audit` y `npm run build`: PASS.
- Auditorías finales: cero vulnerabilidades detectadas en ambos proyectos Composer y npm.
- PHP: 47 ejecuciones correctas, incluyendo sintaxis de los 32 archivos, estudio, capítulos, avatar, traducciones, trials, recuperación de pedidos con dobles, aislamiento de staging y producción, permisos/integraciones y assets de correo.
- JavaScript: 28 pruebas correctas, cero fallos.
- `git diff --check`: PASS.
- Permanecen avisos de dependencias peer y tamaño de bundles; la compilación termina correctamente. No se amplió el alcance para resolverlos.
- Las pruebas PHP son aisladas, sin pagos, envíos ni escritura en bases de datos productivas. No sustituyen la validación funcional del conjunto en staging.

## Dependencias actualizadas

### site/composer.lock

- `secondnetwork/blade-tabler-icons`: v3.46.0 → v3.48.0.
- `wp-plugin/favicon-by-realfavicongenerator`: 1.3.50 → 1.3.51.
- `wp-plugin/html-forms`: 1.6.5 → 1.7.1.
- `wp-plugin/post-types-order`: 2.5 → 2.5.6.
- `wpengine/advanced-custom-fields-pro`: 6.8.9 → 6.8.10.
- `laravel/pint`: v1.30.5 → v1.32.1.
- `roave/security-advisories`: dev-latest → dev-latest.

### site/web/app/themes/sage/composer.lock

- `league/flysystem`: 3.32.0 → 3.36.0.
- `laravel/pint`: v1.30.5 → v1.32.1.

### site/web/app/themes/sage/package-lock.json

- `baseline-browser-mapping`: 2.10.0 → 2.11.26.
- `browserslist`: 4.28.1 → 4.29.3.
- `caniuse-lite`: 1.0.30001776 → 1.0.30001814.
- `colord`: 2.9.3 → 2.10.0.
- `electron-to-chromium`: 1.5.307 → 1.5.443.
- `fast-uri`: 3.1.6 → 3.1.8.
- `fflate`: 0.6.10 → 0.6.11.
- `hls.js`: 1.7.1 → 1.7.3.
- `node-releases`: 2.0.27 → 2.0.57.
- `update-browserslist-db`: 1.2.3 → 1.3.3.

No se añadieron ni eliminaron entradas npm. Se conservaron todos los manifiestos y constraints. No se actualizaron WordPress, PMPro, Acorn, React, Three, Vidstack, Swiper, Vite ni Tailwind dentro de estos dos commits. Siguen omitidas las actualizaciones mayores o sensibles descritas en el informe original.

## Diferencia entre main y producción: nueva decisión necesaria

La lectura directa por SSH de los archivos de la release activa confirma WordPress 7.0.3, PMPro 3.8.6 y ACF Pro 6.8.5. El core real coincide con el lockfile remoto; no se dedujo la versión de una nota antigua ni se arrancó WordPress para esta consulta.

`main` ya contenía WordPress 7.1.2 y una integración de módulos CDE móviles antes de esta intervención. El documento `docs/maintenance/integracion-main-2026-09-29.md` indica expresamente que la integración no desplegó y que el futuro despliegue debe validarse primero en staging. El cierre de [ESP-1894](http://127.0.0.1:3100/ESP/issues/ESP-1894) acredita PMPro sobre WordPress 7.0.3 y excluye 7.1.2 de su sign-off. Que [ESP-1892](http://127.0.0.1:3100/ESP/issues/ESP-1892) figure como done no acredita su despliegue efectivo.

Por tanto, ejecutar ahora `trellis deploy production espaciosutil.org` desde main también publicaría cambios anteriores fuera de la rutina ordinaria. No se ha ejecutado deploy, migración, smoke posterior a deploy ni rollback. La release productiva permanece intacta por esta intervención; no se afirma una auditoría integral de su salud.

## Siguiente acción concreta

Decidir la ampliación del alcance para validar en staging y desplegar posteriormente el conjunto ya integrado de `main`, incluido WordPress 7.1.2 y los módulos CDE. La propuesta es usar `1c79a6d` como candidato, comprobar de nuevo que no haya cambios posteriores, identificar los módulos que se activan, validar staging con respaldo y rollback, y promover a producción solo si esa validación es satisfactoria. No activar proveedores de tienda ni nuevas credenciales como efecto implícito del mantenimiento.

[ESP-2007](http://127.0.0.1:3100/ESP/issues/ESP-2007) pasa a revisión del board por esta decisión concreta. El bloqueo de lint queda resuelto y no se pide revisión rutinaria de lockfiles.

## Conservación de evidencia

El informe original de la ejecución automática se conserva sin cambios. Los logs de esta intervención permanecen en `/private/tmp/esp-2007-evidence`; por ser temporales, este informe recoge resultados, versiones y límites de la comprobación y se adjunta a Paperclip. No se publican registros operativos ni credenciales en GitHub.
