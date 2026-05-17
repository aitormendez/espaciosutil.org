# ESP-159 - Auditoria de sincronizacion WordPress

Fecha: 2026-05-12.

## Resumen ejecutivo

ESP-157 no ejecuto una sincronizacion completa local <-> production. El trabajo
directo de ESP-157 modifico codigo del tema y relaciones PMPro en WordPress
local. Al detectar que `planteamiento-general-que-es-la-realidad` no existia en
local, creo ESP-158. ESP-158 creo/sincronizo solo esa leccion en WordPress
local a partir de artefactos CDE ya validados y snapshots de publicacion previos.

No hay evidencia de escritura en WordPress production durante ESP-157/ESP-158.
Si hubo uso de datos de production, fue como evidencia/snapshot previo para
construir el estado local.

## Que hizo ESP-157

- Run principal: `5f7e95fb-2c67-489c-adad-f736052d0652`.
- Ventana aproximada: 2026-05-12 05:09-05:14 UTC.
- Entorno tocado: WordPress local (`/Volumes/D/Desarrollo/Sites/espaciosutil.org/site`).
- Codigo tocado: tema Sage, con commits:
  - `1320c95 fix(cde): actualiza ctas de leccion gratuita`;
  - despues de resolver ESP-158, `6d020ad fix(cde): resuelve permalink real de leccion gratuita`.
- Escritura PMPro local detectada:

```sql
INSERT IGNORE INTO wp_pmpro_memberships_pages (membership_id, page_id)
SELECT levels.ID, posts.ID
FROM wp_posts posts
JOIN wp_postmeta active
  ON active.post_id=posts.ID
  AND active.meta_key='active_lesson'
  AND active.meta_value='1'
JOIN wp_pmpro_membership_levels levels
  ON levels.ID IN (11,12,13)
WHERE posts.post_type='cde'
  AND posts.post_status='publish'
  AND posts.post_name NOT IN (
    'presentacion-del-curso',
    'planteamiento-general-que-es-la-realidad'
  );
```

Esta escritura fue local, no production. No fue una sincronizacion de base de
datos ni de ficheros entre entornos.

## Que hizo ESP-158

- Run principal: `ad445854-7880-466b-9e3c-feafa9563b40`.
- Ventana aproximada: 2026-05-12 05:13-05:18 UTC.
- Origen: artefactos CDE locales y snapshots/evidencias previas de publicacion
  CDE.
- Destino: WordPress local.
- Mecanismo de escritura:

```bash
CDE_WP_APPLY_PAYLOAD='.../tmp/ESP-158/wordpress_apply_payload.local.json' \
wp eval-file '.../tmp/ESP-158/apply_wordpress_local_free_lesson.php'
```

- Resultado local:
  - post CDE `2776`, `planteamiento-general-que-es-la-realidad`;
  - padre local `2774`, `Seth. Realidad y existencia`;
  - poster subido a la Media Library local;
  - PMPro sin restricciones para la nueva leccion gratuita.
- Evidencia:
  - `tmp/ESP-158/wordpress_publication.dry-run.json`;
  - `tmp/ESP-158/wordpress-apply-result.local.json`;
  - `tmp/ESP-158/wordpress-verification.local.json`.
- Backup previo: no consta dump de BD local; si la operacion se consideraba
  sync, deberia haber requerido snapshot previo. Como escritura acotada local,
  la evidencia de apply/verification permite identificar posts creados.

## Confirmacion sobre production

No se encontro ningun comando de escritura contra production en los runs de
ESP-157/ESP-158. En particular, no aparece ejecucion de
`scripts/cde_wp_cli_production.sh`, `wp @production`, `WORDPRESS_TARGET=production`
en modo apply, import/export de BD, rsync ni herramienta equivalente.

En ESP-159 si se hizo una lectura autenticada de production y una correccion
PMPro acotada:

- Antes:
  - `planteamiento-general-que-es-la-realidad`: `11,12,13`;
  - `presentacion-del-curso`: sin restriccion;
  - `que-es-un-curso-de-milagros`: `11,12,13`;
  - `quien-es-seth`: sin restriccion.
- Escritura aplicada:
  - eliminar PMPro `11,12,13` de `planteamiento-general-que-es-la-realidad`;
  - anadir PMPro `11,12,13` a `quien-es-seth`;
  - `que-es-un-curso-de-milagros` no requeria cambio porque ya estaba correcto.
- Despues:
  - `planteamiento-general-que-es-la-realidad`: sin restriccion;
  - `presentacion-del-curso`: sin restriccion;
  - `que-es-un-curso-de-milagros`: `11,12,13`;
  - `quien-es-seth`: `11,12,13`;
  - control global: `invalid_active_lessons = 0`.

Evidencia local ESP-159:

- `tmp/ESP-159/production-pmpro-before.tsv`;
- `tmp/ESP-159/production-pmpro-after.tsv`;
- `tmp/ESP-159/production-invalid-active-lessons-after.tsv`;
- `tmp/ESP-159/production-free-exceptions-after.tsv`.

## Reglas fijadas

La regla queda documentada en:

- `cde-wordpress-publication/SKILL.md`;
- `docs/cde-wordpress-publication.md`.

Resumen:

- ningun agente debe sincronizar local <-> production sin instruccion explicita
  de Aitor;
- toda sync debe declarar origen, destino, alcance, comando, modo, riesgo,
  backup/snapshot y rollback;
- si la tarea trabaja sobre production, se consulta production directamente;
- que un post no exista en local no autoriza una sync;
- local no es fuente de verdad salvo instruccion explicita;
- production tampoco debe sobrescribir local sin confirmacion;
- para CDE WordPress se prefieren operaciones acotadas sobre una leccion, no
  sincronizaciones completas de entornos.

## Recomendacion

No hace falta rollback. La accion correctiva adicional recomendable es revisar
cualquier issue futuro que mencione "sincronizar" y exigir la declaracion
estricta antes de permitir comandos de escritura.
