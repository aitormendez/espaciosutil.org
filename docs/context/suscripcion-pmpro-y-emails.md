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

## Verificación recomendada

1. Ejecutar `wp eval-file scripts/verify-pmpro-trial.php`.
2. Hacer checkout completo en test mode con Stripe para mensual, semestral y anual.
3. Verificar alta, orden inicial a cero, trial y emails.

## Archivos clave

- `site/web/app/themes/sage/resources/views/template-suscripcion.blade.php`
- `site/web/app/themes/sage/resources/views/partials/page-header.blade.php`
- `site/web/app/themes/sage/resources/views/partials/pricing-table.blade.php`
- `site/web/app/themes/sage/resources/views/partials/pricing-package.blade.php`
- `site/web/app/themes/sage/resources/views/partials/pricing-plan-card.blade.php`
- `site/web/app/mu-plugins/espaciosutil-pmpro-trials.php`
- `site/web/app/mu-plugins/espaciosutil-pmpro-email-preview.php`
- `site/web/app/themes/sage/paid-memberships-pro/email/header.html`
- `site/web/app/themes/sage/paid-memberships-pro/email/footer.html`
