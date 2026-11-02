# Mantenimiento ordinario de dependencias — 2026-10-01

## Resultado

**Ejecución detenida por un bloqueo real de verificación.** `composer run lint` devolvió código 1: Pint 1.32.1 detectó errores de formato en 32 archivos. No se creó commit, no se integró ni publicó `main` y no se desplegó producción. No se pidió revisión humana del mantenimiento ordinario.

La instalación y la auditoría Composer del sitio pasaron. Las actualizaciones se limitaron a tres lockfiles. El código fuente de los archivos denunciados por Pint permanece idéntico a `origin/main`; no se ha determinado si todos los errores también aparecen con Pint 1.30.5. No se ejecutó un autoformateo ni se amplió el alcance para corregirlos.

## Objetivo y preparación

Aplicar mantenimiento patch/minor de bajo riesgo, verificarlo e integrar/desplegar únicamente si todos los gates pasan.

- Issue: ESP-2007. Responsable: CTO.
- Rama: `chore/deps-routine-2026-10`.
- Base: `7b190adddf7bce86e48401a7115eae10002c7f81` (`origin/main` tras fetch).
- Worktree: `/var/folders/by/6ff6_t2d0nj57yj54y8jj16r0000gn/T/paperclip-run-esp-2007-269978dc-b6c-YvJRbX/chore-deps-routine-2026-10`.
- El directorio del run es temporal y Paperclip puede eliminarlo al finalizar. El informe conserva versiones y comandos para reproducir la selección; no se afirma que el worktree sea una entrega persistente.
- Checkout principal: sin cambios tracked iniciales, pero con archivos sin seguimiento. Se conservaron todos. Solo se copia a él este informe solicitado.
- Git fetch y creación del worktree requirieron permisos acotados de red/metadatos Git; se concedieron. La primera llamada local a Paperclip falló por aislamiento de red y funcionó con permiso acotado. No hubo rechazo de aprobación.

Estado inicial del checkout principal:

```text
?? .DS_Store
?? .github/.DS_Store
?? docs/.DS_Store
?? "docs/informes cliente/.DS_Store"
?? "docs/informes internos/mantenimiento-dependencias-rutina-2026-08-01.md"
?? "docs/informes internos/mantenimiento-dependencias-rutina-2026-09-01.md"
?? docs/practicas-cde.md
?? site/.DS_Store
?? site/web/app/plugins/espacio-sutil-blocks/.DS_Store
?? site/web/app/plugins/espacio-sutil-blocks/src/.DS_Store
?? site/web/app/themes/sage/.DS_Store
?? site/web/app/themes/sage/resources/.DS_Store
?? site/web/app/themes/sage/resources/css/.DS_Store
?? site/web/app/themes/sage/resources/fonts/.DS_Store
?? site/web/app/themes/sage/resources/images/.DS_Store
?? site/web/app/themes/sage/resources/images/icons/.DS_Store
?? trellis/.DS_Store
?? trellis/group_vars/.DS_Store
?? trellis/roles/.DS_Store
```

## Inventario y clasificación

Se consultaron los registros Composer/NPM actuales. Por tratarse de un worktree nuevo sin vendor/node_modules, el inventario y las auditorías iniciales usaron `--locked` o `--package-lock-only`.

- Composer sitio: cero avisos.
- Composer tema: un aviso bajo en `league/flysystem`, CVE-2026-102601 / GHSA-cxf4-7mrp-vvpr. La resolución de Flysystem 3.36.0 terminó sin avisos.
- NPM inicial: cinco paquetes vulnerables, dos de severidad alta y tres moderada. Se seleccionaron correcciones de la misma serie compatible para `baseline-browser-mapping`, `browserslist`, `colord`, `fast-uri` y `fflate`.
- NPM update terminó con cero vulnerabilidades según su auditoría incorporada. Esto no sustituye al `npm audit` final pendiente.
- No se cambió ningún manifiesto, constraint o código funcional.

## Actualizaciones preparadas, no publicadas

### site/composer.lock

- `secondnetwork/blade-tabler-icons`: v3.46.0 → v3.48.0.
- `wp-plugin/favicon-by-realfavicongenerator`: 1.3.50 → 1.3.51.
- `wp-plugin/html-forms`: 1.6.5 → 1.7.1.
- `wp-plugin/post-types-order`: 2.5 → 2.5.6.
- `wpengine/advanced-custom-fields-pro`: 6.8.9 → 6.8.10.
- `laravel/pint`: v1.30.5 → v1.32.1.
- `roave/security-advisories`: 2f891ad → 69ddaea (dev-latest).

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


NPM también regeneró metadatos `peer` del lockfile. No añadió ni eliminó entradas (390 antes y después), ni cambió las versiones de React, Three, Vidstack, Swiper, Vite o Tailwind. Quedan avisos peer preexistentes en las familias Leva/r3f/React; no se intentó resolverlos dentro de esta rutina.

## Omitidas y motivos

- `wp-plugin/taxonomy-terms-order` 1.9.9.1 → 2.0: major omitida.
- `roots/acorn` 5.0.6 → 6.3.0: major estructural omitida.
- `infinite-scroll` 5, tsParticles 4, Leva 0.10 y Three 0.186: major o cambio estructural omitido; no se persiguió `latest` fuera de `wanted`.
- React/React DOM 19.3, familias React Three, Vidstack 1.15.6 y Swiper 12.2/14.3: frontend/runtime sensible omitido por necesitar validación específica más allá del mantenimiento de bajo riesgo.
- Vite/Tailwind/Roots Vite plugin: mantenidos; toolchain sensible no incluida en la selección. El inventario NPM sin node_modules no devolvió su listado completo de versiones instaladas, ni siquiera con `--include=dev`; no se afirma haber agotado sus candidatos.
- WordPress 7.1.2 y PMPro: sin cambios. ACF Pro solo patch 6.8.9 → 6.8.10.
- Seguridad que exija major: ninguna entre los avisos detectados.

## Comandos ejecutados

```text
git fetch origin
git status --short
git worktree list --porcelain
git branch --list 'chore/deps-routine-*'
git worktree add -b chore/deps-routine-2026-10 <worktree> origin/main
# En site y en site/web/app/themes/sage:
composer outdated --direct --locked --format=json
composer audit --locked --format=json
# En Sage:
npm outdated --package-lock-only --json
npm outdated --include=dev --package-lock-only --json
npm audit --package-lock-only --json
# En site (primero en seco con --dry-run y después aplicado):
composer update laravel/pint roave/security-advisories secondnetwork/blade-tabler-icons wp-plugin/favicon-by-realfavicongenerator wp-plugin/html-forms wp-plugin/post-types-order wpengine/advanced-custom-fields-pro --with-dependencies --minimal-changes --no-interaction --no-install
# En Sage (primero en seco con --dry-run y después aplicado):
composer update laravel/pint league/flysystem --with-dependencies --minimal-changes --no-interaction --no-install
npm update hls.js baseline-browser-mapping browserslist colord fast-uri fflate --package-lock-only --ignore-scripts
# Verificación, en site:
composer install --no-interaction
composer audit
composer run lint
# Inspección local del diff:
git diff --stat
git diff --check
git status --short
```

No se ejecutó `npm audit fix`, ni se desactivaron controles de seguridad.

## Verificaciones

| Gate | Resultado |
|---|---|
| Composer install sitio | PASS, código 0 |
| Composer audit sitio | PASS, código 0, cero avisos |
| Composer lint sitio | **FAIL, código 1; 32 archivos** |
| Composer install tema | No ejecutado: detención tras lint |
| Composer audit tema final | No ejecutado; update informó cero avisos |
| npm ci | No ejecutado: detención tras lint |
| npm audit final | No ejecutado; update informó cero vulnerabilidades |
| npm run build | No ejecutado: detención tras lint |
| git diff --check | PASS |
| Alcance | Solo los tres lockfiles permitidos |

Archivos con errores de formato, rutas relativas a `site`:

- `web/app/themes/sage/app/View/Composers/SingleCde.php`: control_structure_braces, braces_position, statement_indentation.
- `web/app/mu-plugins/cde-mobile/access.php`: blank_line_after_opening_tag, control_structure_braces, braces_position, statement_indentation.
- `web/app/mu-plugins/cde-mobile/progress.php`: single_space_around_construct, method_argument_space, blank_line_after_opening_tag, control_structure_braces, ternary_operator_spaces, concat_space, control_structure_continuation_position, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces, binary_operator_spaces.
- `web/app/mu-plugins/cde-mobile/study.php`: single_space_around_construct, blank_line_after_opening_tag, control_structure_braces, concat_space, control_structure_continuation_position, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces.
- `web/app/mu-plugins/cde-mobile/account.php`: single_space_around_construct, function_declaration, method_argument_space, blank_line_after_opening_tag, control_structure_braces, ternary_operator_spaces, concat_space, control_structure_continuation_position, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces, binary_operator_spaces.
- `web/app/mu-plugins/cde-mobile/shared-trial.php`: single_space_around_construct, function_declaration, method_argument_space, blank_line_after_opening_tag, control_structure_braces, ternary_operator_spaces, concat_space, control_structure_continuation_position, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces, binary_operator_spaces.
- `web/app/mu-plugins/cde-mobile/stripe-membership.php`: single_space_around_construct, function_declaration, method_argument_space, blank_line_after_opening_tag, control_structure_braces, ternary_operator_spaces, concat_space, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces, binary_operator_spaces.
- `web/app/mu-plugins/cde-mobile/storage.php`: method_argument_space, blank_line_after_opening_tag, control_structure_braces, ternary_operator_spaces, concat_space, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces, binary_operator_spaces.
- `web/app/mu-plugins/cde-mobile/membership.php`: elseif, single_space_around_construct, function_declaration, method_argument_space, blank_line_after_opening_tag, control_structure_braces, ternary_operator_spaces, concat_space, control_structure_continuation_position, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces, blank_line_after_namespace, binary_operator_spaces.
- `web/app/mu-plugins/cde-mobile/api.php`: single_space_around_construct, function_declaration, method_argument_space, blank_line_after_opening_tag, control_structure_braces, ternary_operator_spaces, concat_space, control_structure_continuation_position, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces, binary_operator_spaces.
- `web/app/mu-plugins/cde-mobile/media.php`: single_space_around_construct, method_argument_space, blank_line_after_opening_tag, control_structure_braces, ternary_operator_spaces, concat_space, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces, binary_operator_spaces.
- `web/app/mu-plugins/cde-mobile/registration.php`: single_space_around_construct, function_declaration, method_argument_space, blank_line_after_opening_tag, control_structure_braces, ternary_operator_spaces, concat_space, control_structure_continuation_position, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces, binary_operator_spaces.
- `web/app/mu-plugins/cde-mobile/quiz.php`: single_space_around_construct, blank_line_after_opening_tag, control_structure_braces, concat_space, control_structure_continuation_position, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces.
- `web/app/mu-plugins/cde-mobile/legacy.php`: single_space_around_construct, function_declaration, method_argument_space, blank_line_after_opening_tag, control_structure_braces, ternary_operator_spaces, concat_space, control_structure_continuation_position, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces, binary_operator_spaces.
- `web/app/mu-plugins/espaciosutil-pmpro-localization.php`: blank_line_after_opening_tag.
- `web/app/mu-plugins/espaciosutil-cde-mobile.php`: blank_line_after_opening_tag, control_structure_braces, braces_position, statement_indentation.
- `web/app/mu-plugins/espaciosutil-staging-isolation.php`: blank_line_after_opening_tag, control_structure_braces, braces_position, statement_indentation.
- `tests/cde-course-live.php`: single_space_around_construct, function_declaration, method_argument_space, blank_line_after_opening_tag, control_structure_braces, ternary_operator_spaces, concat_space, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces, single_line_after_imports, binary_operator_spaces.
- `tests/cde-study-live.php`: single_space_around_construct, function_declaration, method_argument_space, blank_line_after_opening_tag, control_structure_braces, concat_space, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces, single_line_after_imports, binary_operator_spaces.
- `tests/pmpro-upgrade-trials.php`: blank_line_after_opening_tag, control_structure_braces, no_multiple_statements_per_line, braces_position, statement_indentation.
- `tests/staging-isolation.php`: concat_space, braces_position, statement_indentation, single_line_empty_body, binary_operator_spaces.
- `tests/pmpro-upgrade-recovery.php`: modifier_keywords, class_definition, blank_line_after_opening_tag, control_structure_braces, no_multiple_statements_per_line, braces_position, statement_indentation, single_line_empty_body, blank_lines_before_namespace.
- `tests/cde-domain-live.php`: single_space_around_construct, method_argument_space, blank_line_after_opening_tag, control_structure_braces, concat_space, control_structure_continuation_position, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces, single_line_after_imports, binary_operator_spaces.
- `tests/pmpro-localization.php`: blank_line_after_opening_tag, control_structure_braces, no_multiple_statements_per_line, braces_position, statement_indentation, binary_operator_spaces.
- `tests/cde-study-unit.php`: single_space_around_construct, function_declaration, method_argument_space, blank_line_after_opening_tag, control_structure_braces, concat_space, braces_position, statement_indentation, binary_operator_spaces.
- `tests/cde-access-live.php`: method_argument_space, blank_line_after_opening_tag, control_structure_braces, ternary_operator_spaces, concat_space, control_structure_continuation_position, no_multiple_statements_per_line, braces_position, statement_indentation, single_line_after_imports, binary_operator_spaces.
- `tests/pmpro-email-assets.php`: method_argument_space, blank_line_after_opening_tag, concat_space, braces_position, statement_indentation, binary_operator_spaces.
- `tests/cde-mobile-audio-chapters.php`: single_space_around_construct, function_declaration, method_argument_space, blank_line_after_opening_tag, control_structure_braces, concat_space, no_multiple_statements_per_line, braces_position, statement_indentation, single_line_after_imports, binary_operator_spaces.
- `tests/cde-mobile-account-unit.php`: class_definition, single_space_around_construct, method_argument_space, blank_line_after_opening_tag, control_structure_braces, concat_space, no_multiple_statements_per_line, braces_position, statement_indentation, single_line_after_imports, single_line_empty_body, binary_operator_spaces.
- `tests/cde-progress-live.php`: single_space_around_construct, function_declaration, method_argument_space, blank_line_after_opening_tag, control_structure_braces, concat_space, no_multiple_statements_per_line, braces_position, statement_indentation, cast_spaces, single_line_after_imports, binary_operator_spaces.
- `tests/staging-live.php`: blank_line_after_opening_tag, braces_position, statement_indentation.
- `scripts/build-pmpro-localization.php`: blank_line_after_opening_tag, control_structure_braces, no_multiple_statements_per_line, braces_position, statement_indentation, binary_operator_spaces.

## Integración, producción y rollback

- Commit: no creado, por gate fallido.
- Integración y push: no ejecutados.
- Deploy: no ejecutado.
- Smoke checks de producción: no ejecutados, al no haber deploy.
- Rollback: no necesario.
- Estado final de producción: **esta ejecución no introdujo cambios en producción**. No se realizó comprobación de su salud actual.

## Bloqueo y siguiente acción

Propietario: CTO. Acción: coordinar la corrección del gate de formato de estos 32 archivos como deuda separada del mantenimiento, determinar la compatibilidad de Pint y obtener un `composer run lint` correcto sobre la base aprobada. Después, reanudar esta misma tarea desde `origin/main` actualizado, repetir la selección y toda la matriz requerida; solo entonces commit, integración, push, deploy y smoke checks. No se deben integrar los lockfiles no verificados ni excluir rutas de lint para forzar el resultado.

Follow-ups técnicos fuera de alcance: migración Acorn 6, revisión conjunta de toolchain/frontend sensible y saneamiento de los avisos peer. No se crearon issues separados para commit/PR ni se activaron heartbeats periódicos.
