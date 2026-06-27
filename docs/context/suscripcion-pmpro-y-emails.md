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
- Requests soportados:

```json
{
  "external_subject": "wp_user:123"
}
```

```json
{
  "session_token": "<cde_access_token emitido por /atlas/>"
}
```

- No se acepta email como identidad primaria ni lookup.
- `session_token` debe ser una prueba CDE efimera emitida por la puerta `/atlas/`. El endpoint valida firma HMAC SHA-256, TTL, `issuer=espaciosutil_cde`, `audience=atlas`, `version=1` y correspondencia `external_subject`/`wordpress_user_id` antes de resolver la membresia actual.
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
  - `403 invalid_session_token`: `session_token` ausente de contrato, manipulado, caducado o con claims invalidos.
  - `400 invalid_subject`: falta `external_subject` o no tiene formato `wp_user:{id}`.
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

## Puerta minima Atlas en CDE

- Plantilla WordPress/Sage: `site/web/app/themes/sage/resources/views/template-atlas.blade.php`.
- Ruta operativa esperada: pagina WordPress con slug `/atlas/` y plantilla `Atlas`.
- La pagina es una puerta funcional para miembros CDE, no una landing comercial de producto.
- Estados resueltos:
  - usuario CDE con membresia activa: muestra "Abrir Atlas" y emite una prueba CDE de corta duracion;
  - usuario logueado sin membresia activa: no emite prueba y enlaza a `Suscripcion`/`Mi cuenta`;
  - usuario no logueado: no emite prueba y enlaza a login con retorno a `/atlas/` y a `Suscripcion`;
  - token no configurable: informa indisponibilidad temporal sin abrir Atlas.
- Helper principal: `espaciosutil_atlas_cde_access_state()`.
- La emision usa `espaciosutil_atlas_cde_issue_access_token()` con payload JSON firmado por HMAC SHA-256.
- TTL por defecto: 5 minutos, filtrable con `espaciosutil_atlas_cde_access_token_ttl` y limitado a un maximo operativo de 15 minutos.
- Secreto recomendado para la firma: `ESPACIOSUTIL_ATLAS_CDE_ACCESS_TOKEN_SECRET`, con fallback a la opcion WordPress `espaciosutil_atlas_cde_access_token_secret`.
- Fallback transitorio: si no existe secreto especifico de acceso, se reutiliza el secreto del endpoint privado de membresia. En produccion conviene separar ambos secretos.
- URL Atlas configurable: `ESPACIOSUTIL_ATLAS_URL`, con fallback `https://atlas.espaciosutil.org/`.
- Parametro emitido hacia Atlas: `cde_access_token`.
- El token incluye sujeto `wp_user:{id}`, estado PMPro normalizado, `level_id`, `issued_at`, `expires_at`, `nonce`, issuer `espaciosutil_cde` y audience `atlas`.
- La ruta `/atlas/` pertenece al contexto CDE y se marca como sensible para evitar transiciones Barba con una prueba efimera en la UI.
- Atlas consume el token limpiandolo de la URL y enviandolo como `session_token` al endpoint privado de membresia; la validacion criptografica vive en WordPress/CDE.

## Navegacion CDE con Atlas

Estructura conceptual aprobada para el menu CDE:

```text
Curso
  Lecciones
  Programa
  Suscripcion
Atlas
Mi cuenta
Switch Espacio Sutil / CDE
```

Notas operativas:

- `Atlas` debe apuntar a `/atlas/` como item top-level del menu CDE.
- `Lecciones` puede reemplazar a `Indice de lecciones` como etiqueta si se hace desde WordPress sin cambiar la URL.
- El switch ES/CDE mantiene su funcion de cruce de contexto y no debe mezclarse con enlaces de cuenta.
- La modificacion de items del menu se hace en WordPress; no se fuerza por codigo desde esta plantilla.

## Verificación recomendada

1. Ejecutar `wp eval-file scripts/verify-pmpro-trial.php`.
2. Hacer checkout completo en test mode con Stripe para mensual, semestral y anual.
3. Verificar alta, orden inicial a cero, trial y emails.
4. Ejecutar `php site/tests/atlas-cde-membership-endpoint.php`.
5. Verificar `/atlas/` como anonimo, usuario logueado sin membresia y usuario con nivel CDE activo.

## Archivos clave

- `site/web/app/themes/sage/resources/views/template-suscripcion.blade.php`
- `site/web/app/themes/sage/resources/views/template-atlas.blade.php`
- `site/web/app/themes/sage/resources/views/partials/page-header.blade.php`
- `site/web/app/themes/sage/resources/views/partials/pricing-table.blade.php`
- `site/web/app/themes/sage/resources/views/partials/pricing-package.blade.php`
- `site/web/app/themes/sage/resources/views/partials/pricing-plan-card.blade.php`
- `site/web/app/mu-plugins/espaciosutil-atlas-cde-membership.php`
- `site/web/app/mu-plugins/espaciosutil-pmpro-trials.php`
- `site/web/app/mu-plugins/espaciosutil-pmpro-email-preview.php`
- `site/web/app/themes/sage/paid-memberships-pro/email/header.html`
- `site/web/app/themes/sage/paid-memberships-pro/email/footer.html`
