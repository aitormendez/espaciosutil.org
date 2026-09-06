# Progreso compartido 0.3.1

Staging, 2026-09-06. Vídeo y audio comparten posición y revisión por alumno y lección. El cambio responde a la comprobación física de la app 0.3: cada medio conservaba un punto independiente.

La posición canónica vive en `cde_lesson_progress_{lessonId}` y la revisión en el recurso `p:lesson:{lessonId}` de la tabla existente. Las claves históricas por Stream ID se mantienen como proyecciones, dentro de la misma transacción y bloqueo por usuario. Los GET no migran: sin estado nuevo eligen la escritura histórica con fecha más reciente (vídeo primero ante empate o ausencia de fecha). No se escoge la posición mayor, para respetar retrocesos. La revisión inicial es la mayor histórica.

El contrato HTTP conserva los campos existentes. GET /progress devuelve una proyección por medio con la misma posición, revisión y fecha. PUT valida identidad, versión y duración del medio seleccionado y aplica revisión/idempotencia a la lección. La posición se expresa en segundos comunes; no hay conversión por porcentaje. La app limita el seek a la duración disponible.

El adaptador web usa `rest_dispatch_request`: `rest_request_before_callbacks` no sustituye un resultado correcto del controlador, que podía ejecutar una segunda escritura o entregar el punto antiguo. Se mantienen los controles previos de autorización. GET y POST web consultan el servicio compartido; el cambio de hook se cubre también con la regresión de cuestionarios. Referencia: [despacho REST de WordPress](https://developer.wordpress.org/reference/hooks/rest_dispatch_request/).

Verificación: 16 aserciones de `site/tests/cde-progress-live.php`, 14 de `site/tests/cde-study-live.php` y 42 de la regresión HTTPS del proyecto móvil. Sólo cuentas sintéticas; las lecciones temporales y sus metadatos, revisiones y recibos se eliminan en finally. El marcado Vista no cambia.

Despliegue acotado: progress.php y legacy.php, con copia privada previa de archivos y base de datos en staging. No se cambian credenciales, red, correo o producción. Para revertir, restaurar ambos archivos respaldados; las proyecciones históricas conservan la última posición. No restaurar toda la base sobre actividad posterior. Si se reactiva 0.3.1 después de un rollback con escrituras antiguas, reconciliar los estados canónicos antes de hacerlo: no basta volver a copiar el código.
