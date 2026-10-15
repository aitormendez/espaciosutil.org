# Columna centrada e índice adaptable — 2026-10-15

Revisión en la lección local «Qué es El libro de Urantia» (2389), donde la práctica 2998 ya estaba asignada. No se modificaron contenidos, relaciones ni membresías. Se usa la previsualización optativa del tema y una sesión autenticada existente.

La sección ocupa el ancho disponible de la página. El visor, su título y enlaces usan la misma anchura máxima `--container-4xl` (56 rem) que el texto y el cuestionario. El índice único queda en el borde izquierdo, fuera de la columna. Se abre automáticamente si dispone de 256 px más 24 px de separación; al cruzar ese límite por redimensionado vuelve al estado automático. La apertura o cierre manual se conserva al cambiar entre guía y lectura.

Cuando no cabe, se oculta; la apertura manual presenta un panel superpuesto que no desplaza ni estrecha la columna. Puede cerrarse con el botón, con Escape o al seleccionar un destino. El foco pasa al control o al destino visible. El índice permanece accesible durante la lectura mediante posicionamiento sticky y scroll propio. En pantalla completa se conserva el comportamiento anterior: comienza cerrado y se abre mediante su botón.

## Verificaciones realizadas

- Compilación Vite y sintaxis JavaScript correctas; `git diff --check` sin errores.
- En Chrome autenticado, anchuras útiles de sección de aproximadamente 1761, 1405 y 1121 px: columna de 896 px, alineación con el texto de la lección y ausencia de desbordamiento horizontal. El índice se muestra automáticamente sólo en la primera anchura. Las medidas incluyen el zoom existente del navegador; se registran los valores CSS reales, no sólo el tamaño solicitado a la herramienta.
- Un solo índice en el DOM. Texto completo conserva el ancho íntegro de la columna y usa los mismos botones para enfocar sus encabezados.
- Apertura y cierre manual, Escape, cierre tras navegar en panel superpuesto, destino del bloque en guía, destino del encabezado 6 en lectura, recuperación automática al ensanchar y conservación del cierre manual entre modos.
- La extensión del navegador rechazó la solicitud de pantalla completa. La comprobación independiente con Playwright CLI y la sesión de QA sí pasó: entrada, apertura del índice, salto al encabezado 6 del texto y salida en la ficha individual que comparte el visor.
- El recorrido reutilizable `site/tests/cde-practicas-lesson-browser.js` se adapta al nuevo contrato y a la lección abierta, sin fijar un post ni modificar su relación. La revisión del incrustado de esta sesión se realizó mediante la herramienta de Chrome; no se presenta ese archivo como ejecutado con una cuenta de QA que carece de acceso a la lección 2389.

[Medidas](medidas-columna-indice.json), [columna e índice lateral](practicas-columna-indice-20261015.png) y [panel abierto sin espacio lateral](practicas-indice-superpuesto-20261015.png). Al terminar se retiró el ajuste temporal de viewport para permitir redimensionar la ventana normalmente. Se mantiene pendiente la aprobación de escritorio antes de diseñar móvil.
