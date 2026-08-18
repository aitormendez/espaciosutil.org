# Integración WordPress/PMPro para CDE–Listmonk

## Alcance

Esta capa pertenece al proyecto web. WordPress sigue siendo la fuente de verdad de la membresía y solo despierta a n8n mediante Action Scheduler. El mu-plugin no contiene lógica ni credenciales de Listmonk y no cambia niveles, precios, trial, pagos o acceso a Atlas.

Archivo principal: `site/web/app/mu-plugins/espaciosutil-cde-listmonk-sync.php`.

## Configuración

Las cuatro variables se configuran fuera de Git:

```dotenv
ESPACIOSUTIL_CDE_LISTMONK_SYNC_ENABLED=false
ESPACIOSUTIL_CDE_LISTMONK_WEBHOOK_URL=https://n8n.example.com/webhook/cde-membership
ESPACIOSUTIL_CDE_LISTMONK_WEBHOOK_TOKEN=<secreto-saliente-dedicado>
ESPACIOSUTIL_CDE_LISTMONK_REST_TOKEN=<secreto-entrante-dedicado>
```

Los dos tokens deben ser distintos del secreto de Atlas. El flag acepta `1`, `true`, `yes` u `on`; cualquier otro valor equivale a apagado. Con el flag apagado no se crean eventos ni se ejecutan llamadas salientes. Los endpoints privados permanecen disponibles si su token está configurado para permitir el dry-run previo al cutover.

## Evento hacia n8n

Hook de Action Scheduler: `espaciosutil_cde_listmonk_process_event`.

Grupo: `espaciosutil_cde_listmonk`.

Contrato `1`:

```json
{
  "event_id": "uuid-v4",
  "external_subject": "wp_user:123",
  "event_kind": "membership_changed",
  "occurred_at": "2026-08-18T16:00:00+00:00",
  "contract_version": "1"
}
```

`event_kind` admite `membership_changed` y `email_changed`. El esquema es cerrado: email, nombre, nivel o cualquier campo adicional invalidan el evento. n8n obtiene el estado actual a través del endpoint de snapshot antes de mutar Listmonk.

La llamada usa `Authorization: Bearer <ESPACIOSUTIL_CDE_LISTMONK_WEBHOOK_TOKEN>`, `Content-Type: application/json`, TLS verificado, cero redirecciones y 20 segundos de timeout.

### Respuestas finales

Éxito, únicamente después del readback de n8n:

```json
{
  "status": "synced",
  "code": "synced",
  "readback_verified": true
}
```

Conflicto terminal recuperable por reconciliación:

```json
{
  "status": "reconciliation_required",
  "code": "duplicate_wp_identity"
}
```

Conexión fallida, `408`, `425`, `429` y `5xx` son reintentables. Se realizan hasta cinco intentos con backoff exponencial de 60, 120, 240, 480 segundos —acotado a una hora— más jitter determinista de hasta el 25 %. Al agotarse, el estado pasa a `reconciliation_required:retry_exhausted`.

`401`/`403`, otros `4xx`, JSON inválido o una respuesta `synced` sin `readback_verified=true` son terminales y se reducen a códigos técnicos permitidos. Nunca se registra el cuerpo remoto.

## API privada de WordPress

Todas las rutas exigen `Authorization: Bearer <ESPACIOSUTIL_CDE_LISTMONK_REST_TOKEN>`.

### Snapshot individual

`POST /wp-json/espaciosutil/v1/cde-membership/snapshot`

```json
{"external_subject":"wp_user:123"}
```

Devuelve identidad estable, `wordpress_user_id`, email y nombre actuales, `membership_status` (`active`, `inactive` o `unknown`), nivel, expiración, fecha de consulta y versión. Los niveles activos son `11`, `12` y `13`; el trial vigente es activo porque PMPro ya lo expone como nivel actual.

### Miembros activos paginados

`GET /wp-json/espaciosutil/v1/cde-membership/members?page=1&per_page=50`

`per_page` queda limitado a `1..100`. La respuesta contiene `items`, `pagination.page`, `pagination.per_page`, `pagination.total`, `pagination.total_pages` y `contract_version`. Solo se seleccionan filas PMPro activas de los niveles CDE cuya fecha de fin no haya pasado.

### Resultado técnico

`POST /wp-json/espaciosutil/v1/cde-membership/sync-result`

```json
{
  "external_subject": "wp_user:123",
  "status": "reconciliation_required",
  "code": "email_owned_by_other_wp_user",
  "event_id": "uuid-v4"
}
```

Exige exactamente `external_subject`, `status`, `code` y un `event_id` UUID v4 válido. Solo admite `synced:synced` o `reconciliation_required` con un código de conflicto/reintento del allowlist del mu-plugin. Rechaza campos adicionales y no acepta nivel, estado de membresía, email ni nombre.

WordPress guarda únicamente cuatro metas técnicas ligadas al ID local del usuario: estado, código saneado, fecha y `event_id`. Un éxito posterior sustituye el conflicto por `synced:synced`. No se guardan payloads, email, nombre ni respuestas de Listmonk/n8n.

## Despliegue

1. Ejecutar desde la raíz: `php site/tests/cde-listmonk-sync.php` y `cd site && composer lint`.
2. Desplegar el código con `ESPACIOSUTIL_CDE_LISTMONK_SYNC_ENABLED=false`.
3. Configurar los tres valores sensibles en el entorno de WordPress; no usar opciones de base de datos como fallback.
4. Verificar con el flag apagado que los endpoints rechazan peticiones sin token y responden a n8n con el token entrante.
5. Ejecutar el dry-run y el apply inicial desde la capa de infraestructura. Resolver colisiones antes del apply.
6. Confirmar que la política de privacidad publicada contiene la sección de comunicaciones necesarias del servicio y el derecho de oposición.
7. En el cutover, activar workflows y cambiar el flag a `true`; no se hace desde este entregable.

## Rollback

1. Cambiar primero `ESPACIOSUTIL_CDE_LISTMONK_SYNC_ENABLED=false` y recargar PHP. Los hooks dejan de encolar y el worker no hace red.
2. Desactivar los workflows de infraestructura conforme a su runbook.
3. Desde el host y con directorio `site`, revisar únicamente el grupo propio:

   ```bash
   wp action-scheduler action list --hook=espaciosutil_cde_listmonk_process_event --group=espaciosutil_cde_listmonk --status=pending
   ```

4. Cancelar solo sus acciones pendientes:

   ```bash
   wp action-scheduler action cancel espaciosutil_cde_listmonk_process_event --group=espaciosutil_cde_listmonk --all
   ```

5. Revertir el release web si es necesario y revocar los dos tokens dedicados. No borrar perfiles, lista, bajas manuales ni metadatos técnicos durante el rollback; son evidencia para diagnóstico y reconciliación.

## Verificación local

```bash
php site/tests/cde-listmonk-sync.php
php site/tests/atlas-cde-membership-endpoint.php
php site/tests/campaign-redirects.php
cd site && composer lint
```

El fixture cubre flag, niveles activos `11/12/13`, trial, inactivo, cambio de email, ausencia de HTTP en hooks, payload sin PII, autenticación, endpoints, paginación, éxito verificado, reintento, jitter, agotamiento y códigos saneados.
