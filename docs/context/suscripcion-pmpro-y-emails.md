# Suscripción, PMPro y Emails

## Alcance

Documento de referencia para la landing de suscripción, la lógica de trial personalizado, el checkout de PMPro y los emails transaccionales.

## Landing de suscripción

- La landing vive en `resources/views/template-suscripcion.blade.php`.
- Reutiliza infraestructura de WordPress, PMPro y componentes Blade del tema.
- El header soporta la variante `membership-landing`.
- La tabla de precios se alimenta de grupos nativos de PMPro.
- La UI actual asume tres frecuencias:
  - mensual
  - semestral
  - anual
- La sección de acceso a series y lecciones se genera automáticamente desde `serie_cde`.

## Trial gratuito personalizado

- Archivo principal: `site/web/app/mu-plugins/espaciosutil-pmpro-trials.php`.
- El trial se configura por código en `espaciosutil_pmpro_trial_configs()`.
- La prueba se considera consumida por cuenta WordPress, no por suscripción.
- Se usa la meta `espaciosutil_pmpro_trial_used`.
- Estado actual:
  - nivel 11: 7 días
  - nivel 12: 7 días
  - nivel 13: 7 días
- Para usuarios elegibles:
  - `initial_payment = 0`
  - `profile_start_date = now + 7 days`
- El acceso se concede al alta, pero el primer cobro queda diferido.

## Checkout y copy de membresía

- El checkout de PMPro ya incorpora copy de trial cuando el usuario sigue siendo elegible.
- El estado de suscripción y los CTA de compra se resuelven desde la propia tabla de planes.
- Si el usuario ya tiene el nivel, el CTA lleva a `pmpro_url('account')`.

## Emails transaccionales de PMPro

- La estrategia es mixta:
  - capa visual común en código
  - algunas plantillas clave en código
  - otras gestionadas desde el editor de PMPro
- Cabecera y pie comunes:
  - `paid-memberships-pro/email/header.html`
  - `paid-memberships-pro/email/footer.html`
- Plantillas clave personalizadas:
  - `default.html`
  - `checkout_paid.html`
  - `checkout_paid_admin.html`
  - `invoice.html`
  - `membership_recurring_trial.html`

## Recordatorios recurrentes

- El recordatorio nativo de PMPro a 7 días no servía para un trial de 7 días.
- Se sustituyó por lógica propia:
  - renovaciones normales: recordatorio a 7 días
  - primer cobro tras trial: recordatorio a 2 días

## Preview y pruebas

- Hay una pantalla de preview de emails en desarrollo:
  - `site/web/app/mu-plugins/espaciosutil-pmpro-email-preview.php`
- El entorno local usa Mailpit para revisar envíos reales.

## Endpoint privado Atlas CDE/PMPro

- Archivo principal: `site/web/app/mu-plugins/espaciosutil-atlas-cde-membership.php`.
- URL REST esperada: `POST /wp-json/espaciosutil/v1/atlas/membership`.
- Uso previsto: consulta backend-to-backend desde Atlas para validar si un usuario WordPress/PMPro tiene acceso Atlas por membresia CDE.
- Autenticacion: cabecera `Authorization: Bearer <token>`.
- Secreto lado CDE: `ESPACIOSUTIL_ATLAS_CDE_MEMBERSHIP_TOKEN`, con fallback operacional a la opcion WordPress `espaciosutil_atlas_cde_membership_token`.
- Variables lado Atlas:
  - `ATLAS_CDE_MEMBERSHIP_URL=https://espaciosutil.org/wp-json/espaciosutil/v1/atlas/membership`
  - `ATLAS_CDE_MEMBERSHIP_TOKEN=<mismo secreto privado>`
  - `ATLAS_CDE_MEMBERSHIP_TIMEOUT_SECONDS`, si se quiere ajustar el timeout del adaptador Atlas.
- Request soportado:

```json
{
  "external_subject": "wp_user:123"
}
```

- No se acepta email como identidad primaria ni lookup.
- `session_token` devuelve `unsupported_session_token` mientras no exista emision/validacion de prueba de sesion CDE.
- Respuesta normalizada:

```json
{
  "provider": "wordpress_pmpro",
  "subject": "wp_user:123",
  "wordpress_user_id": 123,
  "email": "usuario@example.com",
  "display_name": "Nombre visible",
  "membership_status": "active",
  "grants_atlas": true,
  "level_id": 11,
  "level_name": "CDE mensual",
  "expires_at": "2026-07-27T00:00:00+00:00",
  "checked_at": "2026-06-27T12:00:00+00:00",
  "source_version": "espaciosutil_atlas_cde_membership_v1"
}
```

- Estados normalizados:
  - `active`: existe nivel PMPro activo que concede Atlas.
  - `expired`: ultimo estado PMPro conocido `expired`.
  - `cancelled`: ultimo estado PMPro conocido `cancelled`, `admin_cancelled` o `inactive`.
  - `unknown`: usuario inexistente, sin PMPro resoluble o estado no reconocido.
- `revoked` e `invited_manual` no se emiten desde este endpoint en la primera version; quedan como estados Atlas/manuales fuera del origen PMPro.
- Niveles PMPro que conceden Atlas: `11`, `12` y `13`, filtrables con `espaciosutil_atlas_cde_membership_level_ids`.
- Errores esperados:
  - `403 rest_forbidden`: token ausente, invalido o endpoint sin secreto configurado.
  - `400 invalid_subject`: falta `external_subject` o no tiene formato `wp_user:{id}`.
  - `400 unsupported_session_token`: se intenta resolver por token de sesion.
- Rotacion del secreto:
  1. Generar un token fuerte nuevo fuera del repositorio.
  2. Configurarlo en CDE como `ESPACIOSUTIL_ATLAS_CDE_MEMBERSHIP_TOKEN`.
  3. Configurarlo en Atlas como `ATLAS_CDE_MEMBERSHIP_TOKEN`.
  4. Hacer smoke backend-to-backend con un usuario autorizado.
  5. Retirar el secreto anterior de cualquier entorno.
- Limitaciones:
  - No activa `cde_optional` ni `cde_required` en Atlas.
  - No crea login Atlas ni SSO.
  - No modifica pagos, Stripe, planes ni usuarios.
  - No expone progreso CDE, historiales ni datos editoriales.

## Verificación recomendada

1. Ejecutar `wp eval-file scripts/verify-pmpro-trial.php`.
2. Hacer checkout completo en test mode con Stripe para mensual, semestral y anual.
3. Verificar alta, orden inicial a cero, trial y emails.
4. Ejecutar `php site/tests/atlas-cde-membership-endpoint.php`.

## Archivos clave

- `site/web/app/themes/sage/resources/views/template-suscripcion.blade.php`
- `site/web/app/themes/sage/resources/views/partials/page-header.blade.php`
- `site/web/app/themes/sage/resources/views/partials/pricing-table.blade.php`
- `site/web/app/themes/sage/resources/views/partials/pricing-package.blade.php`
- `site/web/app/themes/sage/resources/views/partials/pricing-plan-card.blade.php`
- `site/web/app/mu-plugins/espaciosutil-atlas-cde-membership.php`
- `site/web/app/mu-plugins/espaciosutil-pmpro-trials.php`
- `site/web/app/mu-plugins/espaciosutil-pmpro-email-preview.php`
- `site/web/app/themes/sage/paid-memberships-pro/email/header.html`
- `site/web/app/themes/sage/paid-memberships-pro/email/footer.html`
