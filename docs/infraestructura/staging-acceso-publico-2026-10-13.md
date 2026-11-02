# Acceso público de demostración a staging

Fecha: 2026-10-13. Cambio aplicado y verificado a las 13:03, Europe/Madrid.

## Motivo y autorización

El usuario solicitó que Iván y otras personas pudieran consultar los avances en `https://stage.espaciosutil.org` desde su conexión habitual. Tras explicar que staging conserva cuentas e historial reales, autorizó expresamente retirar la restricción por IP y abrir el acceso desde cualquier conexión. El control de acceso de WordPress y del curso permanece activo.

## Causa y cambio aplicado

Nginx permitía únicamente localhost y los dos dispositivos Tailscale configurados; las conexiones públicas recibían 403 antes de llegar a WordPress. Se añadió `staging_public_access: true` en `trellis/group_vars/staging/main.yml` y una rama condicional en `trellis/nginx-includes/espaciosutil.org/staging-private.conf.j2` que emite `allow all;`. El valor por defecto sigue siendo privado. El bloqueo de importación `staging-import.lock` se conserva en ambos modos.

`trellis/staging-access.yml` admite ambos modos, guarda una copia del archivo anterior, valida Nginx y lo recarga. La ejecución se limitó al servidor de staging `138.68.135.185`: 10 tareas correctas, 2 cambios, 0 fallos. No se desplegó código de aplicación ni se modificó producción. El release activo sigue siendo `20261008105351`. Los cambios locales anteriores del proyecto se conservaron; esta operación no incluye commit ni push.

Copia de configuración anterior en el servidor: `/etc/nginx/includes.d/espaciosutil.org/staging-private.conf.578727.2026-10-13@11:02:27~`.

## Verificación

- Antes del cambio, dos conexiones públicas recibían HTTP 403; después, ambas recibieron HTTP 200 y `X-Robots-Tag: noindex, nofollow` en la portada, con HTTPS válido.
- El formulario `/wp/wp-login.php` devuelve HTTP 200.
- Sin sesión, `/wp-json/cde-mobile/v1/me`, `/wp-json/cde-mobile/v1/account/profile`, `/wp-json/cde-mobile/v1/lessons/2389` y el manifiesto de presentación de la lección 2389 devuelven HTTP 401 con caché privada desactivada.
- WP-CLI, lanzado desde el host contra staging, confirmó `WP_ENV=staging`, cron e indexación desactivados, filtro de correo bloqueado, bloqueo `staging_outbound_disabled` para Stripe, Listmonk y autocompletado de pedidos desactivados, ejecución asíncrona desactivada y PMPro en `sandbox`. La comprobación inspeccionó los filtros sin enviar correos ni ejecutar cobros.
- La plantilla se renderizó correctamente en modo público, privado por defecto y producción; en producción no genera contenido. Las pruebas existentes de aislamiento pasaron para staging, production y development. `git diff --check` no detectó errores.

Estas comprobaciones acreditan la apertura web y la persistencia de los controles revisados; no constituyen una prueba completa de compra o de inicio de sesión con la cuenta de Iván.

## Administración y reversión

Durante la operación, SSH a través del salto por producción empezó a rechazar conexiones. El acceso directo del Mac por su interfaz física funcionó; no se modificaron rutas, Tailscale ni el cortafuegos. La causa del rechazo del salto no se confirmó en los logs.

Para administrar desde un Mac cuya ruta a staging pasa por Tailscale, puede usarse una configuración SSH temporal con `ProxyCommand=/usr/bin/nc -b <interfaz-fisica> -G 10 %h %p`; comprobar primero la interfaz activa, que en esta ejecución era `en1`. Se empleó la cuenta `admin`, clave explícita y la contraseña de sudo del vault mediante Trellis. Se omitió el sondeo automático de root con `--skip-tags connection-tests` y se proporcionó `connection_status.stdout_lines=[]` junto a `ansible_user=admin`.

Para volver al acceso privado, establecer `staging_public_access: false` y ejecutar `staging-access.yml` con `env=staging`, limitado a `138.68.135.185`; comprobar la validación de Nginx y que una conexión pública vuelve a recibir 403. Al conservar `staging_cde_tailscale_enabled: true`, la lista privada vuelve a ser la de los dos dispositivos Tailscale. No cambiar este modo sin atender a la decisión de acceso público registrada aquí.
