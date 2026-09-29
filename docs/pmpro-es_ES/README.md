# Localización acotada de PMPro 3.8.6 para Espacio Sutil

Entrega de ESP-1937 para ESP-1932/B2, 2026-09-29. Implementación web preparada y verificada offline; **no es sign-off funcional ni autorización de activación**. Base: `e32b6afb0359df30c78950c008739e27c99046f8`. Rama: `fix/esp-1937-pmpro-es`. Ninguna modificación de upstream, core, BD, plantillas del tema, pagos o usuarios.

## Archivos y procedencia

- `upstream/`: paquete de procedencia de ESP-1932 conservado completo. ZIP recibido: SHA256 `42cb2e12bf4e1bd4f53af5b0387ee974bb450913edca205b63dfa134e17bda95`. PO oficial: `6b9f9b2ac505bb7912422e68ac206ccbd0f84a33d2055c4ca2a69d6062cf09b6`.
- [Proyecto oficial](https://translate.strangerstudios.com/projects/paid-memberships-pro/es/default/) y [exportación original](https://translate.strangerstudios.com/projects/paid-memberships-pro/es/default/export-translations/?format=po). Traducción comunitaria del proveedor, revisión 2026-01-28; snapshot fijado, no promesa de cobertura total de 3.8.6. Licencia GPLv2 según la cabecera PO y la entrega PMPro; aviso original en `upstream/LICENSE-PMPro.txt`.
- `supplement-es_ES.po`: 54 claves propias de Espacio Sutil, GPL-2.0-only, con referencias, plurales y traducciones revisables. Prima sobre la copia oficial sin modificarla.
- `inventory.csv`: 365 claves requeridas y 281 exclusiones justificadas. Deriva del `coverage.csv` previo, desduplica claves por contexto y añade el shortcode de cuenta y etiquetas JS. No son 365 pantallas ni un porcentaje de aprobación. La base oficial incluye más cadenas fuera del alcance, sin compromiso de traducir todo el administrador.
- `site/scripts/build-pmpro-localization.php`: combina PO oficial + suplemento; falla ante una clave crítica ausente; genera MO, inventario y hashes. Los guiones neutros se conservan literalmente.
- `site/web/app/mu-plugins/espaciosutil-pmpro-localization.php`: único cambio ejecutable en runtime, un filtro de ruta.
- `site/web/app/mu-plugins/espaciosutil-pmpro-l10n/paid-memberships-pro-es_ES.mo`: catálogo combinado versionado. `site/.gitignore` permite incluir este directorio concreto.
- `SHA256.json`: hashes del catálogo desplegable y entradas de construcción. `upstream/SHA256.json` acredita el paquete original. `email-defaults.json`: muestras de los 16 pares de asunto/cuerpo devueltos por los métodos reales de PMPro, todavía con Liquid sin renderizar.

## Carga, orden y límites

El MU-plugin se registra antes de PMPro; no traduce anticipadamente ni fuerza un idioma. PMPro 3.8.6 ejecuta `pmpro_load_textdomain` en `init` prioridad 1, descarga el dominio y carga primero `WP_LANG_DIR/plugins/paid-memberships-pro-<locale>.mo`, luego su directorio local y el registro JIT. `pmpro_init` (prioridad 10) construye los defaults de emails después.

El filtro `load_translation_file`, disponible con argumento locale desde WP 6.6, recibe el candidato final `.mo` o `.l10n.php`. Para **dominio `paid-memberships-pro`, locale efectivo `es_ES`, constante `PMPRO_VERSION === '3.8.6'`**, devuelve el MO propio. Ambos candidatos y ambas rutas nativas terminan en el mismo MO, cuya precedencia ya se resolvió al compilar. Así un PHP de traducciones global/local no puede ocultar el suplemento. No se usan filtros `gettext`, filtros de opciones, escrituras BD, cambios del locale ni desactivación de actualizaciones. Véase [contrato de carga de WordPress](https://developer.wordpress.org/reference/functions/load_textdomain/).

Con PMPro inactivo, otra versión, otro dominio/locale o MO no legible, se conserva la ruta recibida. Un futuro cambio de versión exige revisar y ampliar explícitamente el guard y el POT; no se promete compatibilidad futura. La ruta propia no está dentro de las actualizaciones automáticas de idiomas. El dominio es compartido por frontend y admin: las claves que ambos usan se traducen también en admin, pero no se amplía el inventario a sus paneles.

En Bedrock la ubicación habitual de `WP_LANG_DIR` es `site/web/app/languages`; se debe acreditar su valor **efectivo** en el entorno aislado. El cargador propio usa `__DIR__`, sin suponer document root, dominio o ruta de despliegue. La prueba usa una carpeta global temporal y los fuentes reales de WP 7.1.2/PMPro 3.8.6, no el `WP_LANG_DIR` de un sitio arrancado.

## Inventario crítico y exclusiones

| Superficie | Preparación entregada | Comprobación que depende del entorno |
|---|---|---|
| Cuenta | Shortcode real, sin membresía, enlaces de cambio/renovación/cancelación, historial y perfil | Cuenta anónima, activa y sin nivel; textos de usuario/datos BD |
| Facturación y cancelación | Páginas, preheaders, portal, singular/plural y contexto del guion sin caducidad | Cancelación inmediata/al fin de ciclo, tarjeta enmascarada y fechas |
| Planes 11/12/13 | Periodos, formatos de importe/coste y CTA del catálogo; tema/trial existentes preservados | Nombres y descripciones BD, mensual/semestral/anual, importes efectivos |
| Trial elegible/consumido | MU-plugin de trial y dominio `espaciosutil-pmpro-trials` sin cambios; asunto y cuerpo custom ya españoles | Para cada plan: copy, pago inicial, fecha y primera renovación, cuenta con trial consumido |
| Checkout/preenvío | Campos requeridos, contraseñas, honeypot accesible, código inexistente/caducado/repetido, precios y validaciones | Pantalla completa, foco/lectura asistida, consentimiento y errores seguros |
| Stripe | Mensajes PMPro de conexión, procesamiento, importe discordante, SCA, resultado y portal | Locale del navegador/SDK y respuestas externas; sin cobros en esta entrega |
| Emails | 16 asuntos/cuerpos de respaldo y plantillas de tema intactas | Prioridad BD/tema, datos Liquid/!!tokens!! y render final, sin enviar |

Las reglas por referencias están en `scope_reason()` y cada fila excluida tiene razón. Se excluyen paneles de configuración, conexión y webhooks Stripe, propiedades/métodos internos, deprecaciones, metadatos/ayudas del editor de emails, cambios manuales administrativos, reembolsos, caducidad legacy y pasarelas no usadas. Las notificaciones **transaccionales al administrador** de los flujos seleccionados sí están cubiertas. Los errores genéricos de archivo se incluyen como respaldo, aunque no se añade ningún campo nuevo.

`Your session has expired...` en Stripe:1100 pertenece al handler de administración de webhooks. `Subscription customer ID does not match...` en Stripe:2773 es un diagnóstico interno de sincronización. Ambos están excluidos justificadamente. Los errores fijos alcanzables de checkout permanecen incluidos, aunque su texto sea técnico.

**Frontera externa:** `js/pmpro-stripe.js` inicializa Stripe con `locale: 'auto'` y usa `response.error.message`. Esos textos de SDK/API no pasan por gettext; no los interceptamos ni sustituimos de manera ciega. QA debe probar el navegador español, tarjeta/campo inválido y autenticación segura en el entorno autorizado. No se acredita aquí el español de una respuesta remota dinámica. Tampoco se traducen valores guardados en BD (nombres de plan, mensajes de confirmación) ni dominios de WordPress/otros plugins.

## Emails y prioridad conservada

`PMProEmail::sendEmail()` da prioridad al asunto guardado `pmpro_email_<slug>_subject`. Para cuerpo, conserva el cuerpo guardado cuando procede, luego archivos del tema hijo/padre (localizado/general), archivos de idioma y finalmente defaults/datos. El catálogo solo traduce los defaults; **no resetea opciones ni sustituye textos editoriales guardados**.

Slugs cubiertos: `checkout_paid`, `checkout_paid_admin`, `checkout_free`, `checkout_free_admin`, `invoice`, `membership_recurring`, `billing`, `billing_admin`, `billing_failure`, `billing_failure_admin`, `cancel`, `cancel_admin`, `cancel_on_next_payment_date`, `cancel_on_next_payment_date_admin`, `payment_action`, `payment_action_admin`.

Se mantienen los archivos de tema `default/header/footer/checkout_paid/checkout_paid_admin/invoice/membership_recurring_trial`. Este último conserva el asunto `Tu periodo de prueba en !!sitename!! termina pronto`, registrado por el MU-plugin de trial, y su cuerpo español con `!!renewaldate!!`, `!!billing_amount!!`, `!!cancel_url!!`. No hay modificación de su lógica ni de los scripts de trial. Las plantillas editoriales enumeradas en `docs/pmpro-email-inventory.md` siguen siendo de BD; su comprobación está **pendiente**, no se asume que estén vacías o traducidas.

## Verificación reproducible offline

Desde la raíz del worktree, indicando fuentes instalados de las versiones objetivo y una carpeta temporal propia ya existente:

```sh
php site/scripts/build-pmpro-localization.php /ruta/wordpress-7.1.2
msgfmt --check --statistics docs/pmpro-es_ES/supplement-es_ES.po -o /ruta/scratch/supplement.mo
php site/tests/pmpro-localization.php /ruta/wordpress-7.1.2 /ruta/paid-memberships-pro-3.8.6 /ruta/scratch
php -l site/web/app/mu-plugins/espaciosutil-pmpro-localization.php
git diff --check
```

POMO es el compilador reproducible del MO combinado; `msgfmt` es una validación adicional del suplemento. No se ejecuta `wp eval`, bootstrap, Composer ni migración. Resultado: 365 claves completas, incluidos los seis plurales de cancelación; printf, estructura HTML y tokens intactos; 16 pares de defaults españoles. Muestras: `Página 2`, `Sí, cancelar estas membresías`, `Ya has utilizado el código de descuento indicado.`, `Confirmación de tu membresía en {{ sitename }}`.

La prueba carga hooks, l10n, registry y controller reales de WP 7.1.2 y la función init de localización de PMPro. Usa dobles mínimos de escape/sanitización y locale, y trampas que impiden BD/correo. Prueba precedencia frente a `.l10n.php`, global/local, recarga y aislamiento. **No equivale a WordPress arrancado, PHP-FPM, render Liquid completo, prioridades BD verificadas o smoke de navegador.** La sanitización no se valida con el doble `wp_kses_post`.

## Incorporación y reversión

El chat Codex autorizado conserva en exclusiva staging/producción. Esta tarea entrega commit/patch; no hace merge, despliegue ni activación. Revisar/cherry-pick sobre la base indicada; los dos archivos runtime deben viajar juntos. No copiar nada al checkout compartido ni a `WP_LANG_DIR`.

Rollback de localización: revertir el commit separado, o retirar el MU-plugin cargador y su carpeta de MO. No hay datos que deshacer. En un nuevo request PMPro vuelve a elegir sus catálogos global/local anteriores, incluidos sus `.l10n.php`, que nunca se sobrescribieron. Invalidar caché de página/objeto y OPcache si la política de despliegue lo requiere; comprobar los locales nuevamente. La reversión de PMPro/BD sigue siendo una operación separada de ESP-1931/ESP-1894.

## Checklist exacto de entorno aislado (chat ejecutor + QA ESP-1933)

1. Acreditar aislamiento, WP 7.1.2/PMPro 3.8.6, locale efectivo frontend/usuario y `WP_LANG_DIR`. Enumerar sin modificar `.mo`/`.l10n.php` globales/locales, hash del MO propio y archivo seleccionado por el filtro. Una cuenta `fr_FR`/`es_MX` debe conservar su idioma.
2. Comprobar cuenta sin membresía, activa y expirada; historial/paginación, editar perfil/contraseña, facturación, enlace de portal y CTA de cambio/renovación. Guardar capturas y labels accesibles sin datos personales.
3. Para 11/12/13, comparar tabla de planes y preenvío con trial elegible y consumido: nombre, periodicidad, importe inicial, fecha de primer cobro, importe recurrente y CTA. Confirmar copy de BD y del tema; no inferirlo del MO.
4. En checkout: campos vacíos, email/contraseña inválidos, código inexistente/caducado/usado, honeypot, estado de error Stripe seguro y SCA simulado conforme al smoke autorizado. Verificar textos del SDK con navegador español. No generar cobros reales ni ampliar scripts que crean usuarios.
5. Verificar confirmación de cancelación singular/plural, mantener membresía y cancelación al final de ciclo dentro del smoke autorizado; fechas y mensaje de acceso coherentes.
6. Lectura sin escritura de `pmpro_email_<slug>_{subject,body,disabled}` para los 16 slugs y `membership_recurring_trial`, más `header/footer/default`. Registrar presencia y origen, nunca datos privados en issues. Para slugs en código, confirmar que BD no oculte el tema; si hay contenido guardado, revisarlo con su dueño editorial, **no borrarlo**.
7. Renderizar sin envío asunto y cuerpo efectivo de alta, factura, trial, renovación, fallo, SCA y ambas cancelaciones, incluidas notificaciones admin. Fixture sintético: sitio, nombre, nivel 11/12/13, importe, fecha y URLs. Probar Liquid con condiciones presentes/ausentes y comprobar que no queden `{{…}}`, `{%…%}` ni `!!tokens!!` sin resolver. Comparar diseño del tema y prioridades con BD. No usar botones de “enviar prueba”.
8. Documentar resultado/defecto por pantalla y email. La preparación B2 se devuelve a ESP-1932; la acreditación de interfaz antes de activación y el smoke integral permanecen en sus dueños. Esta entrega no espera al cierre de ESP-1933 ni crea dependencia con ESP-1931.
