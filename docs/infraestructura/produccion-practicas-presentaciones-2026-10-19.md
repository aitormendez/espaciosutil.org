# Prácticas y Presentaciones en producción

El usuario autorizó la entrega conjunta el 2026-10-19. Trellis desplegó `9b3507113e80cce2f36292766e6f751f1290b164` como release `20261019094954`, con cero fallos. La anterior era `20261014100801` (`4d0cc3e`). La rama conjunta conserva el trabajo de prácticas, presentaciones y el comportamiento de finalización de lecciones de main.

## Persistencia y recuperación

La configuración de producción conserva `web/app/uploads`, enlaza `private/cde-practicas-audio` al directorio compartido y declara `CDE_PRESENTATIONS_DIR` en `shared/cde-presentations`. Los dos almacenes privados están fuera de web, con propietario web y permisos 0750. El módulo de Presentaciones admite ahora el entorno production. No se importaron presentaciones, prácticas, relaciones ni datos de staging.

La copia previa está en `/srv/www/espaciosutil.org/shared/backups/practicas-presentaciones-20261019/`, con SQL, entorno y referencia de release. SHA-256 SQL: `04ade915dd9d4d57f20fcbc5187b14f2732e49f84b3ad3da631b465f672c4bb9`. Contiene datos privados y permanece en el servidor. La recuperación usa rollback de Trellis tras comprobar releases disponibles; preservar los almacenes compartidos y no restaurar SQL sobre cambios nuevos sin decisión específica.

## Resultado verificado

- CPT `practica`, Guide/Audio, puente de automatización y servicio privado de Presentaciones disponibles en producción. Relationship `field_cde_practicas` dentro de `group_cde_fields`, conservando el resto del grupo.
- SHA del puente: `389c726e46720f0be2b66fe12e3755fa5d309ac2fa2adf85fdd7744b1a7d3797`, coincidente con el motor 3.5.0 instalado en Contenidos.
- Lectura del outline real EP-0.1 desde el puerto autenticado del motor: 49 pantallas y revisión `0fcdc8400fc9f9d0ea1edec6b4432fc6ecdfa69b96db92f29061802eccbad234`, sin escrituras.
- Menú CDE 60: enlace 3070 «Prácticas», bajo Curso 2878 y después de Lecciones. Los 13 enlaces anteriores conservan campos y orden relativo. Confirmado por API y navegador.
- Portada e índice: HTTP 200. Anónimo: catálogo y audio 403; REST de prácticas y manifiesto/imagen de presentación 401. Respuestas privadas con no-store y sin contenido protegido.
- Compilación del tema y bloques, optimización Acorn y recarga PHP correctas; manifest generado y credenciales temporales de Composer ausentes.
- Pruebas locales nuevas: 5 de series, 4 de límites de audio, 14 de paquete, sintaxis PHP y diff-check. La prueba de paquetes se ejecutó con Python del runtime, que dispone de Pillow. No se atribuyen nuevas pruebas de visor/importación en producción ni QA móvil físico.

## Límites

La consola de despliegue no tiene la clave HMAC de publicación. La comprobación del motor utilizó su puerto de lectura, con destino, wrapper y hash verificados, sin construir permisos de escritura. Sigue pendiente un primer recorrido completo de práctica real, con audio revisado, identidad de la lección resuelta y verificación de ficha, relación y visibilidad autenticada. Producción no contiene fichas de prácticas tras esta entrega; no se copiaron los IDs de staging ni la asociación de diseño 2998→2389.

Las evidencias completas y el informe canónico de la integración del motor están en el repositorio Contenidos: `docs/informes/2026-10-19-practicas-web-produccion.md` y `tmp/practices-production-20261019/`. Esta entrega no modifica releases congeladas de Contenidos ni reabre el seguimiento anterior.
