# PHP: desactivación de session upload progress — 23 de octubre de 2026

## Resultado

Tras revisar el correo `[roots/trellis] Release v1.32.2 - v1.32.2` y el aviso oficial de PHP del 23 de octubre, Aitor autorizó incorporar las dos recomendaciones pendientes: desactivar `session.upload_progress.enabled` y recargar PHP-FPM cuando Trellis cambie su `php.ini`. Esta actuación complementa la [actualización de PHP2Shell del 22 de octubre](php2shell-2026-10-22.md).

| Servicio | Versión verificada por FastCGI | Valor efectivo tras reiniciar |
|---|---|---|
| Producción: WordPress/CDE | PHP 8.4.27 | `session.upload_progress.enabled = Off` |
| Producción: Matomo | PHP 8.4.27 | `session.upload_progress.enabled = Off` |
| Staging: WordPress/CDE | PHP 8.4.27 | `session.upload_progress.enabled = Off` |
| Mac: Homebrew PHP-FPM | PHP 8.5.12 | `session.upload_progress.enabled = Off` |

Se aplicó primero en staging, se verificaron sus páginas y después se aplicó en producción. Los tres servicios PHP-FPM se reiniciaron. Las sondas temporales devolvieron `upload_progress_enabled: false` desde los procesos que atienden las peticiones y se eliminaron al terminar. Las extensiones `curl`, `mbstring`, `intl`, `mysqli`, `gd`, `imagick`, `memcached` y `zip` seguían disponibles en los pools remotos.

## Cambios persistentes

- [Valores por defecto de PHP](../../trellis/roles/php/defaults/main.yml): `php_session_upload_progress_enabled: 'Off'`.
- [Plantilla PHP-FPM](../../trellis/roles/php/templates/php-fpm.ini.j2): `session.upload_progress.enabled = {{ php_session_upload_progress_enabled }}`.
- [Tareas del rol PHP](../../trellis/roles/php/tasks/main.yml): la escritura de `php.ini` notifica al handler existente `reload php-fpm`.
- Mac: se sustituyó la directiva comentada de `/opt/homebrew/etc/php/8.5/php.ini` por `session.upload_progress.enabled = Off`. Ese archivo también lo comparte PHP CLI.

Se incorporaron los cambios concretos de la release a nuestra copia de Trellis; no se hizo una actualización completa de Trellis. Para los servidores existentes se utilizó [php-session-upload-progress.yml](../../trellis/php-session-upload-progress.yml), que respalda y modifica únicamente la directiva en el archivo actual, valida la configuración, reinicia FPM y comprueba cada pool. No se ejecutó el aprovisionamiento completo, no se actualizaron paquetes ni se desplegaron cambios de WordPress. Los vaults siguieron cifrados y sin cambios; se preservó el trabajo local preexistente. No se realizó commit ni push.

La recarga automática quedó incorporada al código del rol y validada mediante la comprobación de sintaxis de `server.yml`; esta operación utilizó un reinicio explícito para reemplazar todos los procesos.

## Comprobaciones

La configuración FPM pasó `php-fpm -t` antes de activar el cambio. Los servicios remotos quedaron activos y la verificación FastCGI pasó en WordPress, Matomo y Homebrew. La primera sonda del Mac se adelantó al arranque del servicio y recibió conexión rechazada; una nueva comprobación, sin otro reinicio, confirmó PHP 8.5.12 con la opción desactivada.

En staging y producción se verificaron HTTP 200 y ausencia de mensajes visibles de error fatal en `/`, `/curso-de-desarrollo-espiritual/`, `/suscripcion/`, `/lecciones-del-cde/seth/planteamiento-general-que-es-la-realidad/`, `/wp/wp-login.php` y `/wp-json/`. Las respuestas de producción evitaron la caché (`Fastcgi-Cache: BYPASS`). También se comprobaron la página principal de Matomo y `/matomo.js`. No se realizaron pagos, cambios de membresía ni pruebas de carga. La prueba autenticada con cuentas sintéticas y la actualización de la VM Lima apagada siguen fuera de esta actuación, tal como consta en el informe anterior.

Pasaron `ansible-playbook --syntax-check` tanto del playbook acotado como de `server.yml`, y `git diff --check`.

## Respaldos y repetición

En ambos servidores, la copia anterior está en `/var/backups/php-upload-progress-20261023/php.ini.before`, dentro de un directorio root `0700`, con archivo `0600`. En el Mac está en `/Users/aitor/.local/state/php-upload-progress-20261023/php.ini.before`, también con directorio `0700` y archivo `0600`. Las copias iniciales no se sobrescriben al repetir el procedimiento.

Desde `trellis/`, para staging:

```sh
trellis exec ansible-playbook php-session-upload-progress.yml -e env=staging --limit 138.68.135.185 --ssh-common-args='-o ProxyJump=admin@134.209.21.17 -o StrictHostKeyChecking=yes'
```

Para producción:

```sh
trellis exec ansible-playbook php-session-upload-progress.yml -e env=production --limit 134.209.21.17
```

El playbook reinicia PHP-FPM y requiere autorización de mantenimiento. Para repetir solo las sondas, añadir `--start-at-task 'Comprobar los pools mediante FastCGI local'`; esa modalidad crea y retira la sonda sin modificar `php.ini` ni reiniciar. En un entorno con restricciones de escritura de Ansible puede establecerse `ANSIBLE_LOCAL_TEMP=/tmp/php-upload-progress-ansible`.

Si fuera necesaria una recuperación, restaurar únicamente el archivo afectado desde su copia, validar la configuración y reiniciar FPM. Restaurar el archivo previo vuelve a activar la función y retira esta protección; no debe hacerse como mantenimiento rutinario.

## Referencias

- [Trellis v1.32.2](https://github.com/roots/trellis/releases/tag/v1.32.2), anuncio recibido y leído en `aitor@e451.net`.
- [Avisos de PHP de octubre de 2026](https://www.php.net/archive/2026.php): recomendación del 23 de octubre para CVE-2026-115560. El vector requiere una combinación poco habitual de configuración y cambios del serializador de sesiones durante la ejecución; no se ha demostrado que esa combinación exista en nuestras aplicaciones.
