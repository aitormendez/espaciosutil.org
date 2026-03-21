# Estrategia de comunicación multicanal

## Estado

- Fecha: 2026-03-19
- Estado: propuesta operativa
- Alcance: Telegram, Instagram, TikTok, YouTube Shorts, YouTube e iVoox

## Resumen ejecutivo

La gestión recomendada para Espacio Sutil es un sistema editorial con tres fuentes de verdad y una capa única de orquestación:

- `WordPress/CPT cde` para las lecciones del Curso de Desarrollo Espiritual.
- `YouTube + iVoox` como origen externo para los episodios de Iván.
- `Base editorial externa` para shorts y campañas, sin crear un CPT específico en WordPress.

La orquestación debe vivir fuera de WordPress, idealmente en `n8n`, y el tablero editorial para contenido de formato corto y campañas se implementará con `NocoDB` autohospedado en el mismo VPS que hoy da servicio a Nextcloud, Collabora y Listmonk. La decisión se apoya en que el uso será interno, con una o dos personas como máximo, y en que el VPS actual tiene capacidad suficiente para esta carga ligera.

## Principios de gestión

- Separar `contenido fuente` de `publicación por canal`.
- No convertir WordPress en un gestor de campañas.
- Normalizar todos los contenidos en una misma entidad operativa antes de distribuirlos.
- Tratar `YouTube Shorts`, `Instagram Reels` y `TikTok` como variantes de un mismo bloque de formato corto.
- Mantener `Telegram` como canal de distribución automatizable.
- Mantener `TikTok` como canal asistido mientras la app de la API no supere la auditoría de TikTok Developers.

## Familias de contenido

### 1. Lección CDE

- Fuente de verdad: `WordPress`, CPT `cde`.
- Referencia técnica actual: [site/web/app/themes/sage/config/post-types.php](/Users/aitor/Documents/Sites/espaciosutil.org/site/web/app/themes/sage/config/post-types.php#L69)
- Objetivo: publicación web y distribución derivada a Telegram y formato corto.

Canales destino recomendados:

- `Telegram`: aviso de nueva lección.
- `Instagram Reels`: clip vertical derivado.
- `TikTok`: clip vertical derivado.
- `YouTube Shorts`: clip vertical derivado.

### 2. Episodio Iván

- Fuente de verdad: `YouTube` e `iVoox`.
- No pasa por WordPress.
- Objetivo: unificar la detección de un episodio publicado en ambas plataformas y disparar la comunicación correspondiente.

Canales destino recomendados:

- `Telegram`: aviso único con enlaces a YouTube e iVoox.

### 3. Short o campaña

- Fuente de verdad: `base editorial externa`, no WordPress.
- Objetivo: producir piezas nativas verticales para crecimiento.

Canales destino recomendados:

- `Instagram Reels`
- `TikTok`
- `YouTube Shorts`
- `Telegram`, solo si una campaña requiere apoyo en comunidad

## Arquitectura recomendada

### Capa de datos

- `WordPress` para el contenido académico del curso.
- `NocoDB` autohospedado para operación editorial de formato corto, campañas, aprobaciones y calendario.

### Infraestructura elegida para `NocoDB`

- Despliegue en el mismo VPS que `Nextcloud`, `Collabora` y `Listmonk`.
- Uso exclusivamente interno.
- Concurrencia prevista: una persona normalmente, dos como máximo.
- Exposición por subdominio propio y proxy inverso en Apache.
- Base de datos separada de Nextcloud.

### Capa de integración

- `n8n` como orquestador.
- Webhooks desde WordPress para cambios relevantes en `cde`.
- Lectura de YouTube por notificaciones o polling controlado.
- Lectura de iVoox por RSS.
- Integración con Telegram Bot API.
- Integración con Instagram Graph API para cuentas profesionales.
- Integración con TikTok Content Posting API solo para flujos asistidos o borradores hasta superar auditoría.

### Capa de almacenamiento de activos

- Repositorio externo de materiales, no WordPress:
  - `Bunny`, `S3`, `Cloudflare R2` o carpeta de producción equivalente.
- Los materiales no deben depender de la biblioteca de medios de WordPress salvo necesidades puntuales del sitio.

## Metodología operativa

### Flujo general

1. `Entrada`
   - Lección nueva en `cde`.
   - Episodio nuevo detectado en YouTube o iVoox.
   - Short/campaña creado manualmente en la base editorial.
2. `Normalización`
   - Todo se convierte en una unidad editorial común.
3. `Preparación`
   - Se asocian materiales, textos y plantillas.
4. `Revisión`
   - Requerida para formato corto.
5. `Publicación`
   - Automática o asistida según canal.
6. `Medición`
   - Registro de URL publicada, fecha, estado y notas.

### Regla por canal

- `Telegram`: automático cuando la unidad esté completa.
- `Instagram`: automatizable, pero con aprobación editorial previa en lecciones y campañas.
- `TikTok`: asistido por defecto hasta tener la app auditada por TikTok Developers.
- `YouTube Shorts`: asistido al principio; automatizable más adelante si el flujo vertical queda estabilizado.

## Modelo exacto de datos

El modelo recomendado se implementa fuera de WordPress. WordPress solo sigue siendo fuente de verdad para `cde`.

Para `lesson_cde`, la base editorial externa no debe duplicar el cuerpo completo de la lección. Debe guardar solo la referencia técnica, los metadatos de distribución, el estado operativo y los materiales derivados para canales externos.

### Tabla `campaigns`

Uso: agrupar shorts y acciones de marketing.

| Campo | Tipo | Obligatorio | Notas |
| --- | --- | --- | --- |
| `id` | uuid/text | si | PK |
| `code` | text | si | único, por ejemplo `cde-lanzamiento-2026-q2` |
| `name` | text | si | nombre visible |
| `objective` | enum | si | `awareness`, `engagement`, `conversion`, `retention` |
| `owner_team` | enum | si | `cde`, `ivan`, `marketing` |
| `status` | enum | si | `draft`, `active`, `paused`, `completed`, `archived` |
| `start_date` | date | no | |
| `end_date` | date | no | |
| `notes` | long text | no | |

### Tabla `content_units`

Uso: entidad editorial canónica. Una fila representa una pieza lógica de contenido, independientemente de cuántas plataformas fuente o destino intervengan.

| Campo | Tipo | Obligatorio | Notas |
| --- | --- | --- | --- |
| `id` | uuid/text | si | PK |
| `family` | enum | si | `lesson_cde`, `episode_ivan`, `short_campaign` |
| `title` | text | si | título canónico |
| `subtitle` | text | no | subtítulo o gancho base |
| `summary` | long text | no | descripción operativa |
| `owner_team` | enum | si | `cde`, `ivan`, `marketing` |
| `source_of_truth` | enum | si | `wordpress`, `youtube_ivoox`, `editorial_board` |
| `source_ref` | text | si | referencia primaria, por ejemplo `wp:cde:1234` |
| `dedupe_key` | text | si | clave para evitar duplicados entre fuentes |
| `canonical_url` | text | no | URL principal visible |
| `series_name` | text | no | serie CDE o serie de Iván |
| `campaign_id` | fk | no | a `campaigns.id` |
| `publish_priority` | enum | si | `low`, `normal`, `high` |
| `workflow_state` | enum | si | `draft`, `ready_assets`, `ready_copy`, `ready_review`, `approved`, `scheduled`, `published`, `archived` |
| `canonical_published_at` | datetime | no | fecha final de publicación base |
| `notes_internal` | long text | no | |

#### Reglas de `source_ref`

- `lesson_cde`: `wp:cde:{post_id}`
- `episode_ivan`: `episode:{serie_slug}:{fecha}:{slug}`
- `short_campaign`: `campaign:{campaign_code}:{piece_slug}`

#### Reglas de `dedupe_key`

- `lesson_cde`: igual a `source_ref`
- `episode_ivan`: misma clave para YouTube e iVoox, de forma que ambos se unan en una sola unidad editorial
- `short_campaign`: combinación de campaña y pieza

### Tabla `source_items`

Uso: registrar cada manifestación fuente real asociada a una unidad editorial.

| Campo | Tipo | Obligatorio | Notas |
| --- | --- | --- | --- |
| `id` | uuid/text | si | PK |
| `content_unit_id` | fk | si | a `content_units.id` |
| `platform` | enum | si | `wordpress_cde`, `youtube`, `ivoox`, `manual`, `drive`, `bunny` |
| `external_id` | text | si | id o guid externo |
| `external_url` | text | no | |
| `detected_at` | datetime | si | cuando el sistema lo detecta |
| `published_at` | datetime | no | fecha real en origen |
| `sync_status` | enum | si | `detected`, `matched`, `enriched`, `error` |
| `payload_json` | json/text | no | captura técnica mínima |

Restricción sugerida:

- índice único por `platform + external_id`

### Tabla `assets`

Uso: inventario de materiales asociados a la unidad editorial.

| Campo | Tipo | Obligatorio | Notas |
| --- | --- | --- | --- |
| `id` | uuid/text | si | PK |
| `content_unit_id` | fk | si | a `content_units.id` |
| `role` | enum | si | `video_master`, `video_vertical`, `audio_master`, `thumbnail`, `cover_vertical`, `caption_file`, `transcript`, `copy_doc` |
| `provider` | enum | si | `wp`, `bunny`, `s3`, `r2`, `drive`, `manual_url` |
| `path_or_url` | text | si | |
| `mime_type` | text | no | |
| `aspect_ratio` | enum | no | `16:9`, `9:16`, `1:1`, `4:5` |
| `duration_seconds` | integer | no | |
| `language` | text | no | por ejemplo `es` |
| `status` | enum | si | `missing`, `processing`, `ready`, `deprecated` |
| `checksum` | text | no | |
| `notes` | long text | no | |

### Tabla `distribution_tasks`

Uso: una fila por unidad editorial y canal destino.

| Campo | Tipo | Obligatorio | Notas |
| --- | --- | --- | --- |
| `id` | uuid/text | si | PK |
| `content_unit_id` | fk | si | a `content_units.id` |
| `channel` | enum | si | `telegram_group`, `instagram_reel`, `tiktok_video`, `youtube_short` |
| `target_account` | text | si | cuenta, canal o grupo destino |
| `publish_mode` | enum | si | `automatic`, `assisted`, `manual` |
| `template_key` | text | si | referencia a plantilla |
| `approval_required` | boolean | si | |
| `state` | enum | si | `draft`, `waiting_assets`, `waiting_review`, `approved`, `scheduled`, `publishing`, `published`, `failed`, `skipped` |
| `scheduled_at` | datetime | no | |
| `published_at` | datetime | no | |
| `primary_asset_id` | fk | no | a `assets.id` |
| `caption_text` | long text | no | texto final para el canal |
| `cta_text` | text | no | |
| `hashtags` | text | no | guardar como lista simple o csv |
| `external_post_id` | text | no | id del post publicado |
| `external_post_url` | text | no | |
| `error_last` | long text | no | último error |

Restricción sugerida:

- índice único por `content_unit_id + channel + target_account`

### Tabla `templates`

Uso: versionar los textos base por familia y canal.

| Campo | Tipo | Obligatorio | Notas |
| --- | --- | --- | --- |
| `id` | uuid/text | si | PK |
| `template_key` | text | si | único, por ejemplo `lesson.telegram.v1` |
| `family` | enum | si | `lesson_cde`, `episode_ivan`, `short_campaign` |
| `channel` | enum | si | `telegram_group`, `instagram_reel`, `tiktok_video`, `youtube_short` |
| `version` | integer | si | |
| `body_template` | long text | si | texto con placeholders |
| `active` | boolean | si | |

Ejemplos de `template_key`:

- `lesson.telegram.v1`
- `episode.telegram.v1`
- `campaign.instagram.v1`
- `campaign.tiktok.v1`
- `lesson.youtube-short.v1`

## Evento normalizado para n8n

Cada origen debe convertirse en este contrato común antes de crear o actualizar registros:

```json
{
  "family": "lesson_cde",
  "source_of_truth": "wordpress",
  "source_ref": "wp:cde:1234",
  "dedupe_key": "wp:cde:1234",
  "title": "La respiración consciente",
  "series_name": "Prácticas esenciales",
  "canonical_url": "https://espaciosutil.org/lecciones-del-cde/la-respiracion-consciente/",
  "canonical_published_at": "2026-03-19T09:30:00+01:00",
  "owner_team": "cde",
  "assets": [
    {
      "role": "video_vertical",
      "path_or_url": "https://assets.example.com/cde/1234/vertical.mp4"
    }
  ]
}
```

## Reglas por familia

### Lección CDE

- Crear o actualizar `content_units` desde WordPress.
- `dedupe_key` fijo por `post_id`.
- Sincronizar solo metadatos necesarios:
  - título
  - excerpt o resumen corto
  - URL canónica
  - serie
  - fecha de publicación
- Generar `distribution_tasks` a:
  - `telegram_group`
  - `instagram_reel`
  - `tiktok_video`
  - `youtube_short`
- No publicar formato corto sin `asset.role = video_vertical`.

### Episodio Iván

- Detectar por YouTube e iVoox.
- Realizar un `upsert` sobre una sola `content_unit`.
- Esperar a que existan ambos `source_items` o lanzar un tiempo de espera editorial de conciliación.
- Publicar un único mensaje en Telegram con ambos enlaces si están disponibles.

### Short o campaña

- Crear manualmente en la base editorial.
- Asociar `campaign_id` cuando corresponda.
- Generar `distribution_tasks` por canal vertical.
- No depender de WordPress para estado, revisión ni materiales.

## Política de automatización por canal

| Canal | Fase 1 | Fase 2 |
| --- | --- | --- |
| `Telegram` | automático | automático |
| `Instagram Reels` | asistido con aprobación | automatizable según plantilla estable |
| `TikTok` | asistido | revisar tras auditoría de app |
| `YouTube Shorts` | asistido | automatizable cuando la producción vertical sea estable |

## Recomendación de herramienta editorial

### Decisión adoptada: `NocoDB` autohospedado

Ventajas:

- Se adapta bien al modelo relacional propuesto.
- Encaja mejor con una operación propia y transferible.
- Facilita integración con `n8n`.
- Evita meter lógica de marketing en WordPress.
- Tiene sentido económico para la fase actual, porque evita un coste recurrente por usuario mientras el curso todavía se está validando.
- Encaja con el VPS existente, que ya soporta servicios internos similares con baja concurrencia.

## Implementación recomendada por fases

### Fase 1

- Desplegar `NocoDB` autohospedado en el VPS interno.
- Bot propio de Telegram.
- Flujo `YouTube + iVoox -> episodio unificado -> Telegram`.
- Flujo `WordPress cde -> lección -> Telegram`.
- Base editorial operativa en `NocoDB` para campañas y verticales.

### Fase 2

- Añadir `Instagram Reels` y `YouTube Shorts` para lecciones y campañas.
- Mantener `TikTok` en modo asistido.
- Versionar plantillas en `templates`.

### Fase 3

- Evaluar auditoría de la app de TikTok.
- Automatizar más canales solo cuando el flujo de materiales y aprobación sea estable.
- Incorporar medición y reciclaje de piezas con mejor rendimiento.

## Decisiones explícitamente adoptadas

- `WordPress` no gestionará shorts ni campañas como CPT.
- `cde` seguirá siendo la fuente de verdad solo para las lecciones del curso.
- Los episodios de Iván seguirán fuera de WordPress.
- La orquestación no debe quedar incrustada en el tema; debe vivir en `n8n` o servicio equivalente.
- La base editorial elegida es `NocoDB` autohospedado en el mismo VPS que `Nextcloud`, con acceso interno y baja concurrencia.
