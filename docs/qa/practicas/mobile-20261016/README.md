# Prácticas: adaptación web móvil — 2026-10-16

Código: `7668a0250f6f122b44615756a86603ff9b302db2`, rama `codex/cde-staging-practicas-presentaciones`. Release de staging comprobada: `/srv/www/espaciosutil.org/releases/20261016083020`. Worktree: `/Users/aitor/.codex/worktrees/cde-staging-practicas-presentaciones/espaciosutil.org`.

## Resultado

El visor comparte distribución compacta por debajo de 1024 px, tanto en ficha como en lección: pestañas, herramientas, contenido y navegación separados. El índice se superpone sin reducir el ancho del texto y se cierra al seleccionar un destino. El catálogo usa una columna. La referencia funcional está en [prácticas CDE](../../../context/practicas-cde.md).

## Validación

- Build Vite, sintaxis JavaScript y `git diff --check` correctos. Trellis finalizó con `failed=0`, `unreachable=0` y `rescued=0`.
- [Recorrido móvil](mobile.json): Chromium con emulación de Android, `isMobile`, `hasTouch` y DPR 2; viewport 320×568, 360×640, 390×844, 430×932, 768×1024 y 844×390. Se ejecutó sobre HTTPS de staging, en un contexto nuevo sin interceptaciones de recursos locales.
- 49 pantallas recorridas a 320 px y las 49 en horizontal: sin desbordamiento horizontal del visor ni solapamiento del contenido con toolbar/footer; navegación visible y controles de al menos 44 px. Se verificó el inicio del desplazamiento al cambiar de pantalla, el final de la guía y la entrada/salida real de fullscreen.
- Índice en guía y lectura: apertura, cierre, selección de destino y foco. Lectura sin desbordamiento. Audio autorizado HTTP 200 y estado activo/inactivo. Cero errores JavaScript en el recorrido móvil.
- [Ficha, catálogo y escritorio](pages-desktop.json): ficha individual a 390 px con el mismo índice, catálogo a 320/390/768 px sin desbordamiento, escritorio a 1024/1440/1920 px manteniendo la columna de 896 px y apertura automática del índice sólo cuando cabe.
- Comprobación adicional de la lección a 390 px en navegador de escritorio: ancho del documento y scroll de 375 px, sin desbordamiento horizontal de la página.
- [Runtime](runtime.json): 49 pantallas y audios, 15 diapositivas, ambas pestañas ACF, filtro de serie heredada, esquema de progreso y aislamiento de staging correctos. Las revisiones de audio y presentación permanecen iguales a las de la entrega anterior.

Prueba reutilizable: `site/tests/cde-practicas-mobile-browser.js`, para `playwright-cli run-code` con una sesión autorizada y una lección/ficha con audio completo. Los screenshots se guardan en `output/playwright/practicas-mobile/` del directorio de lanzamiento del navegador. Las sesiones temporales de QA se retiran al finalizar; no se versionan cookies ni credenciales.

## Capturas

- [Incrustado en la lección](embedded-390.png).
- [Portada en pantalla completa](fullscreen-390.png).
- [Párrafo y audio activo](paragraph-390.png).
- [Índice](index-390.png).
- [Lectura completa](reading-390.png).
- [Catálogo](catalogue-390.png).

Las capturas del visor proceden de la ejecución móvil en staging; la del catálogo corresponde a la validación con viewport de 390 px. Se ha revisado visualmente su distribución. Quedan pendientes la aprobación visual del usuario y la comprobación en dispositivos físicos/Safari iOS. El botón de pantalla completa se ofrece cuando el navegador admite esa API; no se ha añadido una simulación para navegadores sin ella.

## Alcance y reversión

No hay migración de contenidos, relaciones ni audios. No se modificaron producción, el checkout operativo de Contenidos, su certificado o las skills de Paperclip. Si se revierte exclusivamente esta adaptación, el release web anterior es `/srv/www/espaciosutil.org/releases/20261016080930`; conservar la base de datos y el almacenamiento privado compartido.
