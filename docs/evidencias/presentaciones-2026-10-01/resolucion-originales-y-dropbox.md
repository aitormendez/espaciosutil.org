# Resolución de las presentaciones: originales y versiones actuales de Dropbox

Comprobación: 2026-10-01. Lectura de fuentes sin modificar ni sustituir ningún PDF. Evidencia completa: [auditoría Poppler](resolucion-originales-y-dropbox.json).

## Resultado

Las tres copias originales conservadas en Series CDE coinciden byte a byte (SHA-256) con el registro del traslado. Sus 45 páginas no tienen texto extraíble ni fuentes incrustadas; contienen imágenes de 1376 × 768 píxeles según `pdfimages -list`. Esta medida procede de los objetos internos del PDF, no del renderizador. Su página mide 1280,04 × 720 puntos.

Los tres PDF encontrados ahora en Dropbox tienen huellas distintas, páginas de 960 × 540 puntos, fuentes Arial/Georgia incrustadas y texto extraíble en todas sus páginas. Contienen fondos rasterizados, habitualmente de 1672 × 940. Los metadatos de creación indican 1 de octubre de 2026 (los originales indicaban 30 de septiembre); estos metadatos no prueban por sí solos cuándo se sincronizaron.

Se renderizó la página 7 de cada una de las seis copias con Poppler y ancho 3840, conservando proporción. Los seis PNG miden 3840 × 2160. En los originales eso amplía una imagen ya rasterizada; en los nuevos el texto se rasteriza desde fuentes y gana nitidez. Se revisaron visualmente las tres muestras nuevas y la original de Un curso de milagros. Es una prueba de resolución, no una validación editorial completa de las 45 páginas nuevas.

La página 7 original de Un curso de milagros se titula «La reinterpretación del cristianismo», tiene fondo claro y tabla; la nueva se titula «El recorrido de la mente», con fondo oscuro y otro contenido. Por tanto, al menos esta presentación cambia también editorialmente; no debe tratarse como una exportación idéntica a mayor resolución.

## Archivos y trazabilidad

### cde:03:00:0.1

- original: [PDF](</Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos/Series CDE/03 - Un curso de milagros/00 - Presentación/0.1 - Qué es Un curso de milagros/Presentación/0.1. - Qué es Un curso de milagros.pdf>). SHA-256: `c3d325e0476d86769799a4bb6b0dc3ddf9046d82459e95dabca41aa7d1364b18`. Tamaño: 2430229 bytes.
- dropbox_actual: [PDF](</Users/aitor/Library/CloudStorage/Dropbox/AITOR CDE/03 - Un curso de milagros/00 - Presentación/0.1 - Qué es Un curso de milagros/0.1. - Qué es Un curso de milagros.pdf>). SHA-256: `76eadbb6a54d7692e6aeab0eeee2697f356efa15685ad9ba7bee969dae367c67`. Tamaño: 2740506 bytes.

### cde:05:00:0.1

- original: [PDF](</Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos/Series CDE/05 - Kryon/00 - Presentación/0.1 - Quién es Kryon/Presentación/0.1. - Quién es Kryon.pdf>). SHA-256: `a6fa2c0acda281ff691f2e0d046dc83aa69cf875f6abb0f0d5d961827fd42a85`. Tamaño: 2180045 bytes.
- dropbox_actual: [PDF](</Users/aitor/Library/CloudStorage/Dropbox/AITOR CDE/05 - Kryon/00 - Presentación/0.1. - Quién es Kryon/0.1. - Quién es Kryon.pdf>). SHA-256: `985dca4f287dddb66e3abfc784b09e07c3c3dc7e27dd8e59c4597c46fe1d4559`. Tamaño: 2555287 bytes.

### cde:07:00:0.1

- original: [PDF](</Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos/Series CDE/07 - Un curso de amor/00 - Presentación/0.1 - Qué es Un curso de amor - I/Presentación/0.1. - Qué es Un curso de amor - I.pdf>). SHA-256: `9c142a244025f18d93b859891bbc8732634fc7e856eca1200d23cb02c147e368`. Tamaño: 1753008 bytes.
- dropbox_actual: [PDF](</Users/aitor/Library/CloudStorage/Dropbox/AITOR CDE/07 - Un curso de amor/00 - Presentación/0.1 - Qué es Un curso de amor - I/0.1. - Qué es Un curso de amor - I.pdf>). SHA-256: `cf1a48782fd429815d6687083347836f8913bcb94bce06cc8734399a6aca874c`. Tamaño: 2811964 bytes.

## Consecuencia para el flujo

Podemos generar las imágenes 4K localmente a partir de los nuevos PDF; no hace falta pedir imágenes adicionales a Iván. Los fondos no adquieren detalle real al ampliar. La elección y sustitución de fuentes finales queda pendiente: las copias de Series CDE se conservan intactas y Dropbox sigue siendo recepción. El contrato mantiene la conversión desde la ubicación final y la decisión de no extraer texto para acompañar las imágenes.

Los otros cinco PDF originales también conservan texto y fuentes, según la inspección estructural repetida en esta revisión; no se ha realizado aquí una prueba visual 4K de esos cinco.

Comando de la prueba (sin forzar la altura):

```sh
pdftoppm -f 7 -l 7 -singlefile -scale-to-x 3840 -scale-to-y -1 -png "$PDF" "$SALIDA"
```

Para las nuevas páginas de 960 × 540 puntos, 288 ppp corresponde igualmente a 3840 × 2160. Se prefiere indicar el ancho al convertir fuentes de geometrías distintas. No se han cambiado el motor, las skills, los certificados ni la configuración operativa.
