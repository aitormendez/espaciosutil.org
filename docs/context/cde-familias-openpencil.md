# Réplicas actuales CDE en OpenPencil — 2 de noviembre de 2026

## Aceptación de la base de diseño

El 02/11/2026 Aitor confirmó **«Me sirven como base»** tras conocer la incidencia de siete textos y el límite de edición automática del documento. Las dos réplicas se aceptan como base editable para su trabajo de identidad; no se amplía este encargo a reparar OpenPencil. La incidencia técnica descrita abajo no se considera corregida.

## Documento y alcance

Se añadieron **dos layouts editables**, sin nueva identidad ni cambios de código WordPress, al documento ya abierto [Espacio Sutil.fig](</Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Diseño/Espacio Sutil.fig>), pestaña `tab-1`, página **Curso Desarrollo Espiritual** (`0:6`). No se abrió ni reemplazó otro documento; no se eliminaron diseños existentes.

Referencias inspeccionadas con Ego Browser, en un único TaskSpace `14`:

- [Índice de lecciones](http://espaciosutil.test/indice-de-lecciones/): estado inicial, series plegadas.
- [Qué es El libro de Urantia](http://espaciosutil.test/lecciones-del-cde/urantia/que-es-el-libro-de-urantia/): **vista pública sin sesión**, con extracto, subíndice y aviso de membresía; no se inventó contenido de miembro.

## Layouts y localización

| Nombre de capa | ID vivo al terminar | ID del parser de disco* | Posición / tamaño |
|---|---|---|---|
| ACTUAL · Índice de lecciones · Escritorio 1585 | `0:615` | `0:5222` | x6600, y0; 1585 × 2290 |
| ACTUAL · Qué es El libro de Urantia · Vista pública 1585 | `0:1162` | `0:5769` | x8465, y0; 1585 × 2522 |

\* OpenPencil 0.16.0 remapea IDs al serializar y al materializar páginas; resolver siempre por página y nombre, no reutilizar ciegamente los IDs históricos. IDs iniciales anteriores al guardado: `527601162:7` y `527601162:554`.

Los originales terminaban en x6368; los nuevos diseños están a la derecha, sin superposición. Al terminar quedaron seleccionados ambos layouts y aplicado zoom al conjunto (centro 8325,1261).

## Editabilidad y fidelidad

- 547 nodos / 76 textos en índice; 549 nodos / 79 textos en lección.
- Grupos separados de fondo/constelaciones, cabecera global, page header, contenido y pie.
- Fondos de color y trazos editables, incluyendo el rojo CDE actual `#770000`; textos nativos editables, no texto convertido a curvas ni captura plana.
- Logos e iconos SVG originales. Se conservaron contenido, composición, jerarquía, colores y geometrías medidas en DOM; tipografías Rubik y Crimson Pro, con ajuste visual del peso de la serif para el renderizador del editor.
- Las constelaciones son una **recreación vectorial estática**, no un fotograma exacto de la animación. Hay diferencias menores de métrica tipográfica y del desplazamiento causado por la barra de scroll al capturar página completa. No se afirma identidad píxel a píxel ni se implementaron interacciones/responsividad.
- Los textos largos se conservan como líneas/fragmentos editables para mantener el aspecto actual; no constituyen un sistema de auto-layout responsive.

### Previews visualmente correctas

Estas previews muestran el estado correcto **después del refresco local de los glifos** descrito a continuación; no prueban por sí solas que el render tras guardar sea correcto:

- [Índice verificado](../../site/tmp/cde-familias-design/indice-verificado.png).
- [Lección verificada](../../site/tmp/cde-familias-design/leccion-verificado.png).

## Guardado y límite de verificación

Se ejecutó el `save_file` oficial sobre `tab-1`, **sin proporcionar otra ruta**, dos veces. El archivo original fue leído después con el parser instalado, sin reabrirlo ni reescribirlo headless. La última comprobación encontró:

- 10.911 nodos en disco frente a 9.815 originales (+1.096).
- Los dos nuevos frames, geometrías y textos están persistidos.
- Las 14 raíces originales CDE conservan sus propiedades comparadas.
- El multiconjunto de propiedades inspeccionadas (nombre, tipo, geometría, texto y datos tipográficos) conserva los 9.815 nodos anteriores, descontando el remapeo de IDs/referencias. Esto **no es una comparación exhaustiva de toda propiedad binaria**.
- Archivo final: 415.570.492 bytes.
- SHA-256: `8fa5ed6c6c8a71c64a633ecf3fa99690f936e615250c1c4779051a70fbbdab27`.

Evidencias: [recibo final](../../site/tmp/cde-familias-design/save-receipt-final.json), [preservación final](../../site/tmp/cde-familias-design/preservation-final.json).

### Incidencia visual post-save pendiente

**No se puede afirmar fidelidad visual persistente perfecta.** Tras guardar y materializar de nuevo la página, OpenPencil 0.16.0 muestra glifos incorrectos en **siete textos nuevos**: `Curso`, `Atlas`, `Acceder` en cada layout, y una línea del extracto de la lección que empieza por `Dios, el universo…`. Sus caracteres Unicode siguen siendo correctos en el archivo y en las propiedades del nodo.

Un refresco focalizado del texto (añadir y retirar un espacio, volviendo exactamente al contenido guardado) restableció su render correcto. Al volver a guardar el fallo se reprodujo. Se detuvieron los intentos; no se modificaron aplicación, configuración, cachés globales ni diseños previos; tampoco se convirtió texto a curvas.

Evidencia del fallo después del segundo guardado: [índice post-save](../../site/tmp/cde-familias-design/indice-entrega.png) y [lección post-save](../../site/tmp/cde-familias-design/leccion-entrega.png). El nombre de esos archivos **no significa aprobación visual**.

El estado visible final de la réplica se dejó refrescado, con el mismo contenido textual que el disco y ambos layouts seleccionados. Ese último refresco local no se volvió a guardar para evitar otra recurrencia; la aplicación puede marcar cambios aunque el texto final sea idéntico. No se reabrió ni reinició el documento para intentar corregirlo.

El [diagnóstico posterior de glifos](cde-openpencil-glifos.md) encontró dibujos derivados defectuosos guardados sólo en los siete textos afectados y una selección insegura de fuente en el exportador instalado. Documenta una propuesta exclusivamente nodal: relleno degradado con ambos extremos del mismo color para evitar la ruta de glifos guardados, manteniendo el texto editable. No está validada visualmente.

La primera modificación de una prueba posterior expresamente autorizada fue rechazada por OpenPencil: `Document too large for atomic agent editing (maximum 10000 nodes)`. Las lecturas de baseline habían materializado las páginas del documento, que contiene 10.911 nodos. **No se modificó ningún nodo, no hubo guardado y no se intentó eludir el límite.** Se confirmó por lectura que el único texto candidato conserva su relleno SOLID y que el hash del archivo sigue siendo el indicado arriba. La limitación visual persistente permanece vigente. No se intentó reconstruir selección o viewport después de la denegación.

## Herramientas y seguridad

No había CLI `openpencil` en PATH ni en `~/.bun/bin`. Se usó el MCP/RPC oficial autenticado en `127.0.0.1:7600`, con credencial de su configuración local consumida sólo en memoria. Se comprobó que los archivos de evidencia no contienen el token real. No se almacenaron credenciales en este informe.
