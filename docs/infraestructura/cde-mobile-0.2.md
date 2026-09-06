# API móvil 0.2 en staging

Fecha: 2026-09-06. Esta rama amplía exclusivamente el código; no contiene cambios de vault ni configuración privada nueva. Su despliegue autorizado es `stage.espaciosutil.org`. No se ha desplegado en producción.

## Comportamiento

Se añaden documentos nativos de introducción y resumen y un intento de cuestionario reanudable por alumno y lección. Los capítulos se obtienen del contenido de WordPress y las preguntas de ACF. Las soluciones se entregan sólo al validar cada respuesta. Guardado, corrección, finalización y repetición comparten bloqueo por usuario, transacción, revisión y recibo de idempotencia.

El resultado final sigue en `cde_quiz_result_{id}`; el intento actual utiliza `cde_quiz_attempt_{id}`. El envío de cuestionarios desde la web pasa por el mismo servicio y avanza la revisión. Un cliente desactualizado recibe 409. El marcado de Vista y el acceso a otras lecciones no cambian. No se añaden tablas ni se migran usuarios o contenidos.

Rutas nuevas bajo `/wp-json/cde-mobile/v1`: `GET /lessons/{lessonId}/study`, `GET /lessons/{lessonId}/quiz`, `PUT /lessons/{lessonId}/quiz/attempt`. El contrato OpenAPI y las decisiones de producto se mantienen en la rama `codex/app-0-2` de `aitormendez/app-cde-movil`.

## Evidencia

- Seis comprobaciones locales del conversor y normalización: UTF-8, anclas, énfasis, eliminación de contenido activo, listas anidadas, imágenes y selección múltiple.
- 58 comprobaciones HTTP de contrato, permisos, persistencia, recibos, concurrencia, corrección y repetición, usando exclusivamente cuentas sintéticas.
- 14 comprobaciones de integración en staging, incluida la escritura web, cambio de definición, selección múltiple, atomicidad de rechazos y ausencia de marcado automático.
- Lectura de las 92 lecciones publicadas: 505 capítulos, sin bloques no soportados en el contenido existente. Ocho entradas incompletas de cuestionario se omiten con el mismo filtro editorial que ya aplicaba el compositor web; no se ha alterado ese contenido.

Scripts: `site/tests/cde-study-unit.php` y `site/tests/cde-study-live.php`. Este último se niega a operar fuera de staging, utiliza la cuenta sintética activa y crea una lección que elimina en finally junto con sus estados auxiliares. Los recibos publicados sólo contienen nombres de comprobaciones y recuentos.

## Despliegue y reversión

Antes del despliegue se guardaron respaldos privados de la base de datos y de los archivos sustituidos. Se aplicó una actualización acotada de los MU plugins móviles, su bootstrap y el compositor `SingleCde`; no se modificaron claves, red, correo, cron ni integraciones externas. Los archivos se transfieren sin metadatos AppleDouble y se conservan permisos legibles por PHP.

Revertir esos archivos a 0.1 deja intactos los resultados finales compatibles con la web; los intentos nuevos pueden permanecer almacenados. La app 0.1 sigue utilizando sus rutas anteriores. La app 0.2 requiere estas rutas nuevas. No restaurar una base de datos sobre actividad posterior sin evaluar antes qué se perdería.
