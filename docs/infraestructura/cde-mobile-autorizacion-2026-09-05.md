# Ejecución autorizada hasta Android instalable

El usuario autoriza cuatro fases encadenadas: seguridad del backend en staging, API móvil, primera app con el diseño de Penpot y validación/reproducción hasta entregar un Android instalable. Se pueden instalar herramientas, modificar y desplegar staging, habilitar únicamente las conexiones necesarias para Bunny y publicar código/documentación en ramas, excluyendo datos personales y secretos. Producción, gastos nuevos, tiendas y cambios de alcance requieren consulta. Se continúa entre fases cuando las pruebas pasan.

## Fase 1: criterios y errores

Una sesión sin membresía no permite leer ni escribir datos de lecciones restringidas. REST estándar no entrega ACF ni cuerpo premium sin permiso; el editor conserva acceso. Resolver vídeo exige referencia guardada y autorizada o permiso de edición; los vídeos editoriales públicos siguen disponibles. Un ID o biblioteca arbitrarios no sirven para consultar Bunny. Ausencia de sesión: 401; membresía insuficiente: 403; lección no publicada/inactiva: 404; PMPro ausente: 503. La copia privada es el único destino de pruebas y despliegue.

## Fases siguientes

Cerrar las decisiones de sesión y persistencia antes de implementar sus contratos. Comprobar refresh, revocación, separación de cuentas, permisos, reintentos y concurrencia; después conectar la app. Entregar pruebas y APK con limitaciones explícitas, sin afirmar pruebas físicas que no se hayan ejecutado.

## Implementación y verificación

Staging ejecuta la API `cde-mobile/v1`. La corrección de autorización protege REST estándar y rutas heredadas de progreso, completado, cuestionario y resolución de medios. El nonce se conserva en los clientes web y editor. Biblioteca y referencia de medio se comprueban contra el contenido autorizado; el acceso editorial explícito se conserva.

Los módulos están en `site/web/app/mu-plugins/cde-mobile/`. `storage.php` contiene instalación explícita del esquema, sesiones y límites; `progress.php` centraliza la escritura web/móvil; `media.php` resuelve Bunny; `api.php` aplica autorización y contrato. `contract.json` deriva de los esquemas de petición del OpenAPI de la app 0.1.1. Actualizar ambos contratos coordinadamente; no definir cuerpos desde la UI.

El esquema sólo se instala mediante WP-CLI en el host de staging. Antes se comprobó InnoDB y se creó `/home/web/staging-import/before-mobile.sql`, privado. La instalación explícita invoca `\EspacioSutil\Mobile\Schema::install()` dentro de WordPress. No hay activación ni migración automática de producción. Las tablas auxiliares guardan sesiones, límites, revisiones y recibos; `usermeta` sigue siendo canónico. Revertir la API no debe eliminar tablas ni restaurar indiscriminadamente el volcado sobre avances posteriores.

Evidencia sanitizada en `cde-mobile-verificacion-2026-09-05.json`: 34 verificaciones de acceso, nueve de dominio, 13 de aislamiento y 42 HTTPS. `site/tests/cde-access-live.php` y `site/tests/cde-domain-live.php` se ejecutan con `wp eval-file` sólo en staging; el segundo recibe por stdin `test_password` de la cuenta sintética y restaura su contraseña en `finally`. No ejecutarlo mientras otro dispositivo usa esa cuenta: invalida sus sesiones como parte de la prueba. La prueba HTTPS reproducible está en `scripts/test-mobile-api.py` del repositorio de la app.

## Bunny y aislamiento

Se permite únicamente GET HTTPS al detalle de un UUID de vídeo en la biblioteca acordada, sin redirecciones. Pagos, correo, Listmonk, cron, tareas asíncronas y otras peticiones continúan bloqueados. Las variables privadas de Bunny permanecen en el vault local cifrado y en el `.env` del release, modo 640 y propietario web:www-data. El repositorio sólo publica referencias a esas variables con valores por defecto cerrados.

El CDN existente exige la cabecera Referer que la API entrega mediante `request_headers`. Se verificaron manifiesto y primer segmento para vídeo y audio; no se configura caducidad criptográfica ni se modifica la política de Bunny de producción. El navegador de staging puede seguir sujeto a la política de origen del CDN; la prueba acreditada es el reproductor nativo.

## Publicación y despliegue

La rama publicable `codex/cde-mobile-backend` parte de `f8933a7`; incorpora código, pruebas y documentación excluyendo el cambio local de `trellis/group_vars/staging/vault.yml`. El commit local `7150534`, que mezcla configuración privada de la copia, no forma parte de esta rama. No se publican volcados, credenciales, logs privados ni filas de usuarios.

El despliegue probado se aplicó de forma acotada sobre el release `20260905152352`, incluyendo módulos MU, resolver de Bunny y assets compilados de Sage/bloques. La identidad del antiguo commit de ese release no describe por sí sola los archivos actuales. Se conserva un manifiesto SHA-256 de los módulos comparados con staging. Antes de otro deploy Trellis, usar el código actualizado de la rama y el vault privado local; no desplegar con un vault público antiguo que carece de las nuevas variables.
