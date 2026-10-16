# Editor de presentaciones CDE

Estado a 2026-10-15: implementado y probado en espaciosutil.test, incluido el tema de vista previa de Prácticas. Esta versión del editor no se ha desplegado en staging ni producción. Desarrollo en la rama `codex/cde-presentaciones-editor`, separada del checkout operativo de Contenidos.

## Uso

En Lección CDE → Presentación, el repetidor «Diapositivas» permite arrastrar las filas, añadir imágenes JPEG/PNG/WebP, sustituirlas y quitar filas. «Guardar» aplica el orden y contenido al slider. Quitar todas las filas deja la presentación vacía; no recupera implícitamente las páginas importadas. «Mostrar presentación» conserva su funcionamiento.

El campo anidado es un tipo ACF de imagen privada (`cde_private_image`). No crea adjuntos ni URLs en la biblioteca pública de WordPress. Las miniaturas y el contenido usan la autorización de la lección; la previsualización exige permiso de edición. El repetidor se excluye de ACF REST sin retirar los demás campos de Lección CDE.

## Persistencia y fuentes

La importación del PDF sigue convirtiéndose en origen. Su paquete privado e inmutable proporciona las filas iniciales del editor; abrirlo no escribe en la base de datos. Cada fila guarda una referencia `revisión:página` dentro del almacén de la propia lección. Una vez guardadas, las filas ACF determinan el orden publicado. La revisión de la composición cambia al modificar las referencias o su orden, conservando los paquetes originales.

Las subidas manuales aceptan imágenes de hasta 20 MB y 40 megapíxeles. WordPress genera variantes WebP de hasta 3840 píxeles sin ampliar el original y las instala en el almacén privado tras verificarlas. Esto no introduce conversión de PDF en el servidor. La interfaz bloquea el guardado durante la subida y conserva la imagen anterior si falla. Quitar filas no borra físicamente paquetes: una limpieza de archivos huérfanos requiere una operación separada.

El campo NocoDB `presentacion_pdf_ruta` sigue apuntando al PDF final de Series CDE. Editar imágenes en WordPress no modifica ese PDF ni su inventario. Las referencias ACF son el contenido publicado, mientras el PDF conserva la procedencia editorial.

## Importaciones posteriores

`wp cde presentation import` compara `--expected-revision` con la revisión vigente de la composición, además de bloquear importaciones simultáneas. Si las filas difieren del paquete importado, activar otro paquete exige `--replace-editor-slides`; sin esa opción la operación falla antes de sustituir la edición. Guardar la lección manteniendo todas las filas originales no se considera una edición manual.

La sustitución explícita elimina las filas guardadas y activa el nuevo paquete, que vuelve a proporcionar el orden inicial. Para restaurar una edición manual hay que conservar sus filas y activación además del puntero al paquete: restaurar solo `_cde_presentation_revision` no restaura la composición. El bloqueo de importación no sustituye al bloqueo editorial de WordPress entre sesiones; no se ofrece una transacción común entre ACF y CLI.

El motor, sus certificados, las skills y el catálogo de Paperclip no se han modificado. Su futura integración debe respetar el rechazo de sobrescritura y decidir expresamente cuándo sustituir ediciones manuales. El contrato del manifiesto web mantiene su forma y numera las páginas según el orden publicado; la integración nativa sigue pendiente.

## Validación local

- Importador y servicio: 23 comprobaciones, incluyendo orden, eliminación total, referencias inválidas, idempotencia, revisión esperada, sustitución explícita y paquete ausente.
- Editor contra WordPress: 8 comprobaciones de variantes, idempotencia, filas guardadas, revisión, borrado total, archivo inválido y aislamiento entre lecciones. El script crea y retira su propia lección temporal.
- ACF: comprobación de la pestaña, claves, repetidor y exclusión REST en tema normal y vista previa de Prácticas.
- Navegador: añadir, arrastrar, sustituir, eliminar, guardar y recargar una lección temporal. Una subida inválida conserva la selección anterior. Manifiesto e imagen privada responden correctamente con permiso; el manifiesto de Urantia sin sesión devuelve 401.
- Regresión: Urantia conserva sus 15 diapositivas y carga el slider con el tema de vista previa. No se ha guardado ni modificado la presentación editorial real durante estas pruebas.

Comandos desde el host, adaptando las rutas del paquete y del worktree:

```sh
php scripts/cde-presentations/test_import.php /ruta/al/paquete
# Desde site:
CDE_TEST_IMAGE=/ruta/a/imagen-3840.webp wp eval-file /ruta/al/worktree/scripts/cde-presentations/test_editor_live.php
wp eval-file tests/cde-presentation-fields-live.php
CDE_PRACTICAS_PREVIEW=1 wp eval-file tests/cde-presentation-fields-live.php
```

La aplicación local conserva los archivos anteriores en `site/tmp/presentation-editor-before-20261015/`. Se sustituyeron los módulos propios de presentaciones y solo el bloque de su pestaña en ambos temas, manteniendo los cambios ajenos. Las dependencias existentes emiten avisos de deprecación PDO bajo PHP 8.5; las comprobaciones terminan correctamente.

## Assets de la vista previa local

El 2026-10-15 se encontró que `site/tmp/cde-practicas-preview/theme/public/build` no contenía el visor de presentaciones, aunque los imports sí estaban en sus fuentes. Esto dejaba el HTML sin estilos y detenido en «Cargando diapositiva…». Se reconstruyó directamente desde ese tema con `npm run build -- --base=/app/themes/sage-practicas-preview/public/build/`, conservando sus fuentes combinadas. Copiar un build generado desde otra rama que carezca de presentaciones vuelve a introducir el problema; los assets deben corresponder al conjunto de fuentes servido.

El visor usa utilidades Tailwind en Blade y en los nodos que crea JavaScript. `app.css` declara explícitamente el archivo de JavaScript como fuente de clases para que también se incluya al compilar la carpeta temporal ignorada por Git. Se retiró `layouts/presentation.css`; `layouts/lesson.css` solo conserva las reglas de integración del fondo de Prácticas. Comprobados ambos temas locales, las 15 diapositivas, navegación, apertura/cierre ampliado y ancho móvil de 390 píxeles sin desbordamiento.
