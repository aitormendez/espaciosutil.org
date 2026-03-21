# Estructura Legal y Cookies

## Alcance

Documento de referencia para la estructura legal publicada, su integración en el sitio y la implementación propia del consentimiento de cookies.

## Estado general

- El plugin `gdpr-cookie-compliance` ya no se usa.
- El consentimiento de cookies se gestiona desde el tema Sage.
- El email legal y de privacidad actualmente usado es `admin@espaciosutil.org`.

## Páginas legales publicadas

- `Aviso legal` (`/aviso-legal/`, ID `2684`)
- `Política de privacidad` (`/politica-de-privacidad/`, ID `3`)
- `Política de cookies` (`/politica-de-cookies/`, ID `2686`)
- `Condiciones de contratación y suscripción` (`/condiciones-de-contratacion-y-suscripcion/`, ID `2687`)

Configuración asociada:

- `wp_page_for_privacy_policy = 3`
- `pmpro_tospage = 2687`
- El menú `footer` incluye un bloque `Legal` con las cuatro páginas.

## Integración legal en el sitio

- El formulario de contacto incluye aceptación obligatoria de la política de privacidad.
- La validación del formulario comprueba captcha y consentimiento.
- PMPro enlaza a la página publicada de condiciones.
- El checkout exige una aceptación expresa de inicio inmediato del contenido digital.

## Banner y panel de cookies

- El banner es propio y ligero.
- Acciones disponibles:
  - `Aceptar`
  - `Rechazar`
  - `Configurar`
- El panel de preferencias también puede abrirse desde el footer mediante `Configurar cookies`.
- Categorías actuales:
  - técnicas: siempre activas
  - analíticas: opt-in

## Persistencia del consentimiento

- La cookie de consentimiento es la fuente de verdad.
- `localStorage` solo se usa como sincronización auxiliar.
- Si se borra la cookie, también se limpia `localStorage` y el banner reaparece.
- Se corrigió además la apertura/cierre del panel para sincronizar correctamente la clase `hidden`.

## Integración con Matomo

- Matomo solo se carga si el usuario acepta analítica.
- El tracking se dispara tanto en carga inicial como en transiciones Barba.
- La parte infra y DNS de Matomo se documenta en `matomo.md`.

## Archivos clave

- `site/web/app/themes/sage/app/helpers.php`
- `site/web/app/themes/sage/app/setup.php`
- `site/web/app/themes/sage/resources/views/forms/contacto.blade.php`
- `site/web/app/themes/sage/resources/views/partials/cookie-consent.blade.php`
- `site/web/app/themes/sage/resources/views/sections/footer.blade.php`
- `site/web/app/themes/sage/resources/views/layouts/app.blade.php`
- `site/web/app/themes/sage/resources/js/cookieConsent.js`
- `site/web/app/themes/sage/resources/css/components/cookie-consent.css`
- `site/web/app/themes/sage/resources/css/commons/forms.css`
- `site/web/app/themes/sage/resources/views/page.blade.php`
