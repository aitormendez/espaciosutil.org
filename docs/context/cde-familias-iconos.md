# Símbolos de familias CDE

Autor: **Side Project**. Colección: [Sacred geometry — Noun Project](https://thenounproject.com/browse/collection-icon/sacred-geometry-281897/).

## Contención de la presentación pública

La cabecera conserva el nombre, color y valor de familia, pero **no renderiza los símbolos** mientras la licencia de estos cuatro archivos no esté acreditada. La contención de ESP-3189 se limita al parcial `lesson-family-badge.blade.php`: no modifica datos WordPress, el catálogo ni la herencia. Las fuentes vectoriales se conservan para trazabilidad; su presencia en el repositorio no acredita autorización de uso.

No restaurar el render de símbolos ni publicar una afirmación de licencia sin evidencia específica y revisión humana explícita. La auditoría y el crédito legal se gestionan separadamente en ESP-3188; no bloquean la clasificación de series de ESP-3182.

## Origen de exportación

Exportados mediante la operación oficial de solo lectura `export_svg` de OpenPencil, desde los vectores originales de las tarjetas familiares. No se han reconstruido ni trazado capturas de pantalla.

- Documento activo verificado: `tab-1`, **Espacio Sutil** (`Espacio Sutil.fig`).
- Página verificada: `0:5`, **Curso Desarrollo Espiritual**.
- Fecha de exportación: 2026-11-02.

| Familia | Archivo | Nodo vector / nombre original | Tarjeta | Tamaño del nodo (px) | viewBox exportado |
| --- | --- | --- | --- | --- | --- |
| Serie esencial | [mercaba.svg](../../site/web/app/themes/sage/resources/images/icons/cde-families/mercaba.svg) | `0:7089` / Mercaba | `68884891:39` | 83.53712463378906 × 96.00355529785156 | `0 0 83.54 96` |
| Serie principal | [torus.svg](../../site/web/app/themes/sage/resources/images/icons/cde-families/torus.svg) | `0:7092` / Torus | `68884891:40` | 96.00814819335938 × 96.00382232666016 | `0 0 96.01 96` |
| Serie espiritual | [triangle-spiral.svg](../../site/web/app/themes/sage/resources/images/icons/cde-families/triangle-spiral.svg) | `0:7091` / Triengle-spiral | `68884891:41` | 96.00177001953125 × 83.54078674316406 | `0 0 96 83.54` |
| Serie complementaria | [cubes.svg](../../site/web/app/themes/sage/resources/images/icons/cde-families/cubes.svg) | `0:7090` / Cubes | `68884891:42` | 96 × 92.5010986328125 | `0 0 96 92.5` |

`Triengle-spiral` es el nombre literal del nodo de origen. El redondeo de coordenadas y viewBox es el producido por el exportador oficial, sin normalización adicional.

## Ajustes técnicos

Se conserva exactamente el atributo `d` de cada path y el `viewBox` devuelto por OpenPencil, incluidos sus espacios y proporciones. Únicamente se eliminan `width` y `height` de la raíz para permitir tamaño CSS y se reemplaza `fill="#FFFFFF"` por `fill="currentColor"`. Cada símbolo es un path de contornos rellenos: no se añaden strokes. No contienen fondo, rótulos, scripts, imágenes ni recursos externos. El namespace XML no carga recursos externos.

Usar SVG inline para heredar `currentColor`; un SVG cargado mediante `<img>` no hereda el color del elemento HTML padre.

## Licencia

**Pendiente de confirmar la licencia aplicable a estos cuatro recursos en su fuente o comprobante de descarga.** La página de colección confirma el crédito Side Project, pero no se ha verificado que la descarga concreta esté bajo CC BY 3.0. No se afirma esa licencia ni se incluye una autorización de uso inferida. Conservar el crédito y verificar los términos aplicables antes de publicar.

## Medidas de referencia del header

Grupo `0:6956`, **03 Page header EDITABLE**, dentro del frame `0:6536`:

- Icono Mercaba `68884891:57`: 53.53772531971034 × 59.23031475571861 px; y = 49.
- Etiqueta `68884891:58`: Serie esencial, Rubik 24 px / peso 400, caja 155 × 28 px; y = 117.76968524428139.
- Gap vertical entre cajas icono/etiqueta: 9.53937048856278 px.
- Título `0:6962`: Crimson Pro 56.58000183105469 px / peso 500, caja 591 × 63 px; y = 163.
- Gap vertical entre cajas etiqueta/título: 17.23031475571861 px.

Las tarjetas familiares usan Rubik 24 px / peso 700; no confundir esa tipografía con la etiqueta del header.
