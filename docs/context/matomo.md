# Matomo

## Alcance

Documento de referencia para aprovisionamiento, dominios por entorno, DNS, variables de entorno e integración de tracking de Matomo.

## Aprovisionamiento con Trellis

- Existe un rol propio `trellis/roles/matomo`.
- Está integrado en:
  - `trellis/dev.yml`
  - `trellis/server.yml`
- La configuración por entorno vive en:
  - `trellis/group_vars/development/matomo.yml`
  - `trellis/group_vars/staging/matomo.yml`
  - `trellis/group_vars/production/matomo.yml`

## Hosts por entorno

- `development`: `http://matomo.espaciosutil.test`
- `staging`: `https://matomo.stage.espaciosutil.org`
- `production`: `https://matomo.espaciosutil.org`

## DNS y resolución

- En local puede ser necesario añadir `matomo.espaciosutil.test` a `/etc/hosts`.
- Para entornos reales hacen falta registros DNS antes del provisionado:
  - `matomo.stage.espaciosutil.org`
  - `matomo.espaciosutil.org`

## Particularidades de instalación

- La versión fijada de Matomo es `5.8.0`.
- La instalación inicial se completa por web, no por CLI.
- El pool PHP de Matomo oculta deprecations para no romper el instalador.
- Trellis incluye `php-gd` porque Matomo lo necesita.

## Sincronización entre entornos

- `site/scripts/sync.sh` sincroniza también la base de datos de Matomo.
- Soporta `--skip-matomo`.

## Tracking frontend

- El tracking está condicionado al consentimiento analítico.
- `app/helpers.php` expone en `js_data()`:
  - `cookieConsent.matomo.url`
  - `cookieConsent.matomo.siteId`
- `resources/js/cookieConsent.js` configura `_paq`, carga `matomo.js` y borra cookies `_pk_*` si no hay consentimiento.

## Variables de entorno relevantes

- `MATOMO_URL`
- `MATOMO_SITE_ID`

Si `MATOMO_URL` no existe, el tema intenta derivarlo como `matomo.<host actual>`.
Si `MATOMO_SITE_ID` no existe, cae por defecto a `1`.

## Recomendación operativa

- Usar configuración separada para `staging` y `production`.
- Si comparten la misma instancia, usar al menos `siteId` distintos.
- Si usan instancias distintas, mantener también subdominios distintos.

## Archivos clave

- `trellis/roles/matomo`
- `trellis/group_vars/development/matomo.yml`
- `trellis/group_vars/staging/matomo.yml`
- `trellis/group_vars/production/matomo.yml`
- `site/scripts/sync.sh`
- `site/web/app/themes/sage/app/helpers.php`
- `site/web/app/themes/sage/resources/js/cookieConsent.js`
