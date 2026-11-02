# ESP-2007 — cierre de mantenimiento y despliegue

Fecha: 1 de octubre de 2026. Autorización: el usuario pidió en el chat de Codex resolver el acceso a staging, validar el conjunto y dejarlo desplegado en producción. No queda ninguna confirmación pendiente que deba tramitar el usuario.

## Resultado final

Staging y producción están operativos con WordPress 7.1.2, PMPro 3.8.6 y las dependencias de octubre, incluido ACF Pro 6.8.10. El bloqueo original de Pint está corregido y el mantenimiento está publicado en main. La API móvil conserva sus guardas: se carga en staging; no se ha habilitado CDE_MOBILE_ENABLED ni proveedores de tienda nuevos en producción.

- Staging: aplicación de `4b2ae7f`, release `20261001070650`, Trellis `ok=57 changed=27 unreachable=0 failed=0`.
- Producción: `325fb19`, release `20261001071522`, Trellis `ok=55 changed=28 unreachable=0 failed=0`.
- Entre ambos commits solo cambia `trellis/group_vars/production/main.yml`; el árbol de aplicación es idéntico al validado en staging.
- Release anterior conservada y respaldada: `20260929-pmpro386` en cada servidor.

## Incidencias resueltas

1. Formato: Pint 1.30.5 y 1.32.1 denunciaban exactamente los mismos 32 archivos y reglas sobre la base inicial. Dos pasadas de autoformato producen un resultado aceptado por ambas versiones. La comparación AST, normalizando visibilidad pública implícita y else-if/elseif, no detecta cambios de lógica. Commit `5a9908c`.
2. Dependencias: tres lockfiles actualizados sin modificar manifiestos, constraints ni incluir majors dentro del mantenimiento. Commit `1c79a6d`. Detalle en el informe de desbloqueo anterior.
3. HTTP 403 de staging: Nginx permitía una IP pública anterior. Se cambió únicamente la lista de IP del vault cifrado por la actual y se aplicó `staging-access.yml`. Se verificaron HTTP 200 desde la conexión del usuario y HTTP 403 desde el servidor de producción. Staging continúa privado. Commit `4b2ae7f`.
4. SSH de staging: la entrada de la IP estaba obsoleta aunque el dominio tenía una identidad válida. Se recuperó la clave ED25519 pública por la conexión verificada al dominio, se comprobó que DNS apuntaba al host esperado y se sustituyó la entrada de la IP conservando copia de known_hosts. No se desactivó la verificación de host.
5. Primer despliegue productivo: Composer recibió HTTP 401 al descargar ACF Pro porque no estaban definidos sus repositorios autenticados para producción. Se reutilizaron las variables cifradas compartidas, igual que en staging, para connect.advancedcustomfields.com y composer.advancedcustomfields.com. Commit `325fb19`. El reintento terminó correctamente. Las credenciales temporales se eliminaron y no se imprimieron ni versionaron valores privados.

El primer fallo productivo ocurrió antes de cambiar el enlace current; la web permaneció en su release anterior. No fue necesario ejecutar rollback ni restaurar una base de datos.

## Validación

- Ocho gates obligatorios de mantenimiento: instalación y auditoría Composer del sitio y tema, lint, npm ci, npm audit y build correctos. Cero vulnerabilidades detectadas en las auditorías finales.
- 47 ejecuciones PHP aisladas y 28 pruebas JavaScript correctas.
- Staging vivo: 13 comprobaciones de aislamiento, 34 de acceso, 14 de estudio y cuestionarios y 16 de curso/progreso; total 77, sin fallos. Fixtures sintéticos con limpieza; no pagos reales.
- Producción: 12 comprobaciones correctas de WordPress, PMPro, ACF, tema, planes, acceso premium de un miembro activo, redacción de contenido anónimo, trial y ausencia de activación móvil implícita. Sin crear sesiones de usuario ni transacciones de pago.
- En ambos entornos: siete rutas críticas comprobadas —inicio, login, admin con redirección esperada a login, suscripción, checkout, índice y lección—, todas responden correctamente; ocho assets del tema por entorno, sin errores.
- Revisión en navegador de suscripción y checkout, sin errores de consola ni imágenes rotas. El checkout conserva la prueba de siete días y el precio mensual esperado; no se envió el formulario ni se hizo un cobro real.
- `wp core update-db --skip-plugins --skip-themes` confirmó en ambos entornos que la base ya estaba en la versión 61833; no hubo migración pendiente. PMPro conserva esquema 3.84.
- Antes/después: conteos de usuarios, contenido, niveles, membresías, pedidos y suscripciones sin diferencias en ambos entornos. Producción conserva 79 registros históricos de membresía, 49 pedidos y 18 suscripciones, cinco de ellas activas.
- Huellas de configuración crítica de PMPro, pasarela y plugins activos: idénticas antes/después. Comparación normalizada del .env: solo cambian GIT_SHA y RELEASE_VERSION en ambos entornos.
- Nginx de producción: ningún error nuevo observado desde el inicio del despliegue definitivo, incluidos errores fatales PHP o timeouts de upstream.
- Git: los cuatro commits están publicados en main; checkout principal actualizado por fast-forward; cambios y documentos ajenos conservados. Vaults versionados cifrados.

## Respaldos y reversibilidad

Cada servidor conserva en `/srv/www/espaciosutil.org/shared/backups/esp-2007-staging` o `esp-2007-production`, respectivamente, una copia comprimida de la base de datos, archivo del código de la release anterior, configuración privada y referencia a la release anterior. Directorios privados; archivos de datos y configuración restringidos al usuario web. Se comprobó la integridad gzip. Los datos y credenciales no se descargaron ni se adjuntaron a Paperclip.

La release anterior se conserva para revertir código. Una restauración de base de datos exigiría preservar/reconciliar escrituras posteriores; no se realizó ni se presenta como una prueba de restauración. No hubo cambios en el número de esquema WordPress ni PMPro durante esta intervención.

## Límites de la validación

No se hicieron cobros reales, nuevas suscripciones, cancelaciones reales, envíos de correo ni compras Apple/Google. Las comprobaciones prueban el despliegue, la conservación de configuración/datos, los permisos y el render del checkout; no constituyen una nueva acreditación de entrega Stripe/SMTP ni una prueba de carga. Los avisos de bundles grandes y peers ya documentados no impidieron compilación ni despliegue.

## Cierre

[ESP-2007](http://127.0.0.1:3100/ESP/issues/ESP-2007) se cierra como completada con este informe. La tarjeta antigua expiró por el comentario del board y queda sustituida por la autorización explícita posterior y esta ejecución terminada. No queda ninguna acción manual para el usuario dentro de este mantenimiento.
