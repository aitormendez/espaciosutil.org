# Diagnóstico de glifos en las réplicas CDE de OpenPencil

Fecha: 2 de noviembre de 2026. Diagnóstico de lectura; **no se ha aplicado una corrección**. No se operó el documento vivo, el navegador ni la configuración de OpenPencil. Esta documentación es la única escritura autorizada del diagnóstico.

Relacionado: [réplicas CDE, evidencias y alcance de preservación](cde-familias-openpencil.md).

## Síntoma y evidencia comprobada

Siete textos de las dos réplicas nuevas muestran glifos incorrectos después de guardar y materializar la página. Añadir y retirar un espacio devuelve el render correcto hasta el siguiente guardado. El informe de réplica acredita esa secuencia; no se repitió durante este diagnóstico.

Se leyó el archivo original de aproximadamente 415 MB con el parser instalado, exclusivamente en memoria y sin volcarlo. Los 10.911 nodos seguían presentes. Los siete textos afectados contienen `derivedTextGlyphs` —dibujos vectoriales derivados de los caracteres, no el contenido textual— mientras que los controles visualmente correctos no los contienen:

| Texto | IDs del parser de disco en esta lectura | Glifos derivados |
| --- | --- | --- |
| Curso | `0:5631`, `0:6178` | 5 por nodo |
| Atlas | `0:5632`, `0:6179` | 5 por nodo |
| Acceder | `0:5633`, `0:6180` | 7 por nodo |
| Dios, el universo… | `0:6202` | 96 |
| Qué es El libro de Urantia —control— | `0:6195` | 0 |
| Descubre qué es —control— | `0:6199` | 0 |

En todos esos nodos: caracteres Unicode correctos, familia `Crimson Pro`, peso `500`, cursiva desactivada, `styleRuns=[]` y `fontVariations=[]`. **No se encontró un override tipográfico por rango que explicara las siete corrupciones.** Los IDs pueden cambiar al serializar o materializar; cualquier operación futura debe resolver por página, raíz nueva y texto/nombre, no reutilizar estos IDs ciegamente.

## Mecanismo comprobado en el código instalado

1. El contenido Unicode se serializa separado de los dibujos derivados: [exportTextData:2592–2603](</Users/aitor/.volta/tools/image/packages/@open-pencil/mcp/lib/node_modules/@open-pencil/mcp/node_modules/@open-pencil/fig/dist/node-change2.js#L2592-L2603>).
2. Al exportar, si el nodo ya tiene glifos derivados se conservan. Si no los tiene, se vuelven a generar: [buildNodeDerivedTextData:2794–2812](</Users/aitor/.volta/tools/image/packages/@open-pencil/mcp/lib/node_modules/@open-pencil/mcp/node_modules/@open-pencil/fig/dist/node-change2.js#L2794-L2812>).
3. Al dibujar texto normal, OpenPencil prefiere esos dibujos guardados al párrafo tipográfico: [renderText:513–519](</Users/aitor/.volta/tools/image/packages/@open-pencil/mcp/lib/node_modules/@open-pencil/mcp/node_modules/@open-pencil/core/dist/canvas/scene.js#L513-L519>).
4. La detección de la fuente que produjo un fragmento acepta una candidata cuando coincide **un solo identificador de glifo**. No exige que coincidan todos los caracteres simples del fragmento: [drawsOwnGlyph:43–48 y runOutlineSource:57–66](</Users/aitor/.volta/tools/image/packages/@open-pencil/mcp/lib/node_modules/@open-pencil/mcp/node_modules/@open-pencil/core/dist/canvas/text/placed.js#L43-L66>).
5. Tras aceptar esa candidata, el exportador extrae de ella los contornos de **todos** los identificadores del fragmento: [shapeText:48–63](</Users/aitor/.volta/tools/image/packages/@open-pencil/mcp/lib/node_modules/@open-pencil/mcp/node_modules/@open-pencil/core/dist/canvas/text/shape.js#L48-L63>).

La combinación permite hornear contornos de caracteres ajenos sin alterar el Unicode. El mecanismo de persistencia y preferencia de los derivados explica por qué un refresco del texto corrige la vista temporalmente pero no evita la recurrencia. La validación de fuente por una sola coincidencia es una debilidad concreta del código, no una comprobación suficiente de identidad de fuente.

### Hipótesis pendiente sobre el origen del desacuerdo de fuentes

Una explicación compatible es que el proveedor tipográfico conserve subconjuntos descargados anteriores mientras el extractor de contornos consulta únicamente los bytes primarios actuales:

- [registerAndCache:483–495](</Users/aitor/.volta/tools/image/packages/@open-pencil/mcp/lib/node_modules/@open-pencil/mcp/node_modules/@open-pencil/core/dist/text/fonts.js#L483-L495>) conserva los bytes anteriores como fuente suplementaria y registra los nuevos.
- [loadedData:348–349](</Users/aitor/.volta/tools/image/packages/@open-pencil/mcp/lib/node_modules/@open-pencil/mcp/node_modules/@open-pencil/core/dist/text/fonts.js#L348-L349>) devuelve sólo los bytes primarios.
- [getParsedFont:5–16](</Users/aitor/.volta/tools/image/packages/@open-pencil/mcp/lib/node_modules/@open-pencil/mcp/node_modules/@open-pencil/core/dist/text/opentype.js#L5-L16>) usa esos bytes para extraer contornos.

**No se ha demostrado que ése sea el estado concreto de las fuentes de la aplicación viva**, que quedaba fuera del alcance. No afirmar que se identificó el archivo exacto de fuente o subconjunto causante. Tampoco se ha verificado que la aplicación empaquetada y el paquete MCP instalado sean idénticos byte a byte; las referencias anteriores son del paquete instalado que usa el parser de las evidencias.

## Propuesta exclusivamente nodal, aún no aplicada

Hay una alternativa distinta de los refrescos fallidos que no requiere cambiar tipografía, convertir texto a curvas ni parchear la aplicación:

> En los siete nodos TEXT nuevos, sustituir el relleno SOLID por un GRADIENT_LINEAR cuyos dos extremos tengan exactamente el mismo RGBA original, conservando opacidad, visibilidad y modo de fusión.

Un degradado entre colores idénticos produce un color constante. El texto, fuente, peso, tamaño, geometría y editabilidad pueden conservarse. No es una corrección del serializador: es una forma local de evitar la ruta de render que usa sus dibujos derivados defectuosos.

Fundamento:

- [canDrawSavedText:192–194](</Users/aitor/.volta/tools/image/packages/@open-pencil/mcp/lib/node_modules/@open-pencil/mcp/node_modules/@open-pencil/core/dist/canvas/text/derived.js#L192-L194>) rechaza los dibujos guardados cuando el relleno no es SOLID.
- [renderText:547–550](</Users/aitor/.volta/tools/image/packages/@open-pencil/mcp/lib/node_modules/@open-pencil/mcp/node_modules/@open-pencil/core/dist/canvas/scene.js#L547-L550>) dibuja entonces un párrafo tipográfico con su pintura. [drawPaintedText:493–502](</Users/aitor/.volta/tools/image/packages/@open-pencil/mcp/lib/node_modules/@open-pencil/mcp/node_modules/@open-pencil/core/dist/canvas/scene.js#L493-L502>) conserva el camino de layout de texto, no crea nodos vectoriales.
- Se ejecutó únicamente la función pura instalada con un fixture en memoria. Resultado: `canDrawSavedText(..., SOLID) = true`; `canDrawSavedText(..., GRADIENT_LINEAR) = false`. No fue una operación del documento ni una exportación.
- La herramienta oficial admite degradados de dos extremos: [set_fill:9–77](</Users/aitor/.volta/tools/image/packages/@open-pencil/mcp/lib/node_modules/@open-pencil/mcp/node_modules/@open-pencil/core/dist/tools/modify/paint.js#L9-L77>). Sin embargo, esa herramienta sustituye el relleno y fija `opacity=1`: una eventual implementación debería copiar explícitamente las propiedades originales, no asumir opacidad o color por defecto.

### Límites y aceptación de una futura prueba autorizada

La alternativa es **viable por análisis de código, no una solución visual persistente verificada**. Para aceptarla hace falta una única prueba autorizada, limitada a esos nodos, que compruebe:

1. Identificación de las siete capas dentro de las dos raíces nuevas, sin tocar diseños originales.
2. Color constante exacto y conservación de texto, fuentes, medidas, posición, opacidad y editabilidad.
3. Comparación visual antes de guardar, después de guardar y después de materializar de nuevo la página.
4. Persistencia del tipo de relleno y de ambos extremos idénticos.
5. Preservación de los originales con el mismo alcance de comparación empleado en las evidencias de réplica.

Hay un límite explícito: si la fuente se considera agotada/no disponible (`exhausted`), [renderText:530–542](</Users/aitor/.volta/tools/image/packages/@open-pencil/mcp/lib/node_modules/@open-pencil/mcp/node_modules/@open-pencil/core/dist/canvas/scene.js#L530-L542>) puede volver a los glifos guardados incluso con ese relleno. El refresco visual previo demuestra disponibilidad en aquella sesión, no garantiza todos los estados futuros o lectores de Figma externos. Diferencias de rasterización también requieren comparación visual, aunque el color matemático sea idéntico.

No repetir añadir/retirar espacios, borrar únicamente los derivados o guardar de nuevo sin más: el mismo exportador puede regenerar el defecto. No se propone cambiar fuentes, convertir a curvas, alterar cachés globales ni modificar los 9.815 nodos originales. Si la prueba nodal autorizada falla, detenerse y conservar la limitación documentada; no ampliar a una reparación global por este encargo.

## Prueba nodal posterior autorizada: rechazada antes de modificar

Después de cerrar el diagnóstico de lectura se autorizó una única prueba de relleno, primero en un texto nuevo y sólo después en los siete si funcionaba. Se comprobó por lectura autenticada que `tab-1`, la ruta original y la página `0:6` seguían siendo las esperadas. Los siete textos coincidían con el archivo guardado en Unicode, nombre, geometría, familia, peso, tamaño y color, sin diferencias que indicaran edición humana de esas capas.

Se capturaron propiedades relevantes **actuales** de las seis páginas visibles y de los 10.911 nodos del archivo, sin asumir que los IDs vivos coincidían con los antiguos. La página interna del archivo no es una página visible del listado de documentos; no se eliminó. Las consultas de propiedades materializaron las páginas para su lectura sin cambiar la página activa CDE.

La primera petición de mutación, `set_fill` sobre el nuevo `Atlas` del índice (`0:1025` vivo), fue rechazada por OpenPencil:

```text
Document too large for atomic agent editing (maximum 10000 nodes)
```

**Cero modificaciones aplicadas y cero guardados durante esta prueba.** Una lectura posterior confirmó `Atlas` con su relleno SOLID original, y el SHA-256 del archivo seguía siendo `8fa5ed6c6c8a71c64a633ecf3fa99690f936e615250c1c4779051a70fbbdab27`. No se efectuó el resto de la prueba ni se intentó eludir el límite descargando páginas, cambiando herramienta, configuración o aplicación. La propuesta permanece pendiente de una validación visual persistente por una vía soportada.

Evidencias locales:

- [Preflight de identidad y propiedades](../../site/tmp/cde-familias-design/glyph-fix-preflight.json).
- [Snapshot vivo de CDE](../../site/tmp/cde-familias-design/glyph-fix-live-before.json).
- [Snapshot vivo de todas las páginas visibles](../../site/tmp/cde-familias-design/glyph-fix-pages-before.json).
- [Snapshot actual de propiedades del archivo](../../site/tmp/cde-familias-design/glyph-fix-disk-before.json).
- [Rechazo y comprobación de ausencia de cambios](../../site/tmp/cde-familias-design/glyph-fix-denied.json).
