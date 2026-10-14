# Prácticas CDE: wireframes y arquitectura propuesta

Fecha: 2026-10-12. Estado: propuesta de diseño realizada en Penpot; pendiente de revisión e implementación. Archivo: Espacio-Sutil-CDE. Página: Práctica - wireframe. La documentación canónica se mantiene aquí y los SVG se conservan en `wireframes/`. No se ha modificado código operativo ni se ha instalado nada en Paperclip.

## Implementación de escritorio del 2026-10-14

La propuesta de este documento y sus SVG corresponden a una iteración anterior. Tras los ajustes en Figma y el regreso a Penpot, el escritorio aprobado usa modo oscuro y controles de audio y pantalla completa. La implementación real utiliza un parcial Blade compartido y un controlador JavaScript pequeño, sin añadir React al visor. El HTML editorial adaptado es la única fuente; el servidor lo convierte de forma determinista en pantallas y calcula una huella que vincula cada audio a su texto. No hay progreso persistente ni contrato de pantallas para la app todavía. Esta decisión sustituye para el MVP la propuesta inferior de un documento independiente con IDs estables; dicho contrato deberá concretarse antes de incorporar progreso persistente o la app. Los textos con bloques no adaptados conservan la lectura completa sin generar una guía incompleta.

Consultar [alcance, voz y validación local](../../practicas-cde-visor-y-voz.md). Los bocetos móviles históricos no están aprobados ni se han actualizado en esta implementación.

## Orden de revisión acordado

Por indicación de Aitor, revisar y aprobar primero los layouts de escritorio. No adaptar ni actualizar los layouts móviles hasta que el desktop esté aprobado. Los bocetos móviles ya creados se conservan como exploración previa, sin aprobación y fuera de la revisión actual. Esta regla se aplica tanto al catálogo como a la ficha y al incrustado.

## Criterio de pantallas

Cada bloque de párrafos empieza con una única pantalla que muestra su ruta completa de encabezados. Por ejemplo, `1. De dónde viene esta práctica` junto con `1.1. En este corpus, canalizar es permitir que la energía pase`. Al llegar al siguiente bloque se repite el padre y aparece `1.2. Una práctica que Tobías propuso a los asistentes`. Para tres o más niveles, se muestran todos, con el último más destacado. No añadir una pantalla previa solo para un padre sin prosa propia.

Después de la cabecera, cada párrafo ocupa una pantalla. No separar las explicaciones subordinadas que ya se integraron con punto y seguido. Mantener el orden editorial aprobado: contexto, ritmo, avisos y ejecución al final. En EP-0.1 hay 15 cabeceras y 28 párrafos: 43 pantallas de contenido, sin contar una eventual portada. Si otra práctica tiene prosa directamente bajo un padre, ese bloque sí necesita su cabecera con la ruta disponible; no limitar el algoritmo a hojas ni a dos niveles.

## Tres vistas, un visor compartido

| Vista | Contenido y propósito | Elemento reutilizado |
| --- | --- | --- |
| Catálogo | Navegación CDE con pestaña Prácticas, búsqueda, filtro por serie y tarjetas. El ejemplo muestra únicamente la práctica realmente conocida. Mostrar filtros según el volumen disponible. | Tarjeta de práctica. |
| Práctica individual | Título, visor, índice, lectura continua y consulta inversa de lecciones relacionadas. | Visor compartido. |
| Práctica dentro de la lección | Bloque después del contenido docente, mismo visor y enlace a la ficha completa. El alumno puede practicar sin abandonar la lección. | El mismo visor; cambia el contexto exterior. |

Los layouts principales tienen versiones de escritorio (1440 px) y móvil (390 px). Los móviles son documentos desplazables de web responsive, no pantallas nativas finales. La altura del visor puede crecer con el texto; no recortar párrafos para forzarlos a caber en un alto fijo. La captura del párrafo más largo muestra ese crecimiento. Tras avanzar, llevar el foco y, cuando haga falta, el scroll al comienzo del contenido del visor, sin volver al inicio de la lección. El diseño se probará también a 320/360 px y con zoom durante la implementación.

El prototipo incluye navegación entre catálogo, ficha y lección en ambas anchuras. En la ficha móvil hay un recorrido enlazado de cuatro pantallas: cabecera 1.1, primer párrafo, segundo párrafo y cabecera 1.2, con retroceso. El resto de botones representa controles pendientes de implementar; no se simulan las 43 pantallas completas. Las ilustraciones de estados no acreditan acceso a NocoDB ni publicación de la práctica.

## Arquitectura propuesta para Sage

Se recomienda un componente Blade con entradas explícitas para el visor, y parciales pequeños para las envolturas de página. La web ya monta sus medios CDE con React desde Blade, según `docs/context/arquitectura-cde-y-frontend.md`; seguir ese patrón permite alojar un único visor con estado propio. No se necesitan dos reproductores ni un iframe.

| Capa propuesta | Responsabilidad |
| --- | --- |
| Dominio en `mu-plugins/cde-practicas` | Identidad, membresía, relaciones y contrato validado del contenido. No depender de la plantilla ni del post global para determinar permisos. |
| Composers o servicio de presentación Sage | Preparar los datos autorizados de una práctica para cualquiera de las dos vistas. Evitar consultas duplicadas y preparar las relaciones visibles una sola vez. |
| `components/practicas/card.blade.php` | Tarjeta del catálogo con título, extracto y enlace. |
| `components/practicas/viewer.blade.php` | Recibir `practice`, `context` (`page` o `lesson`), `instanceId`, `initialScreenId` opcional y nivel semántico de encabezado. Renderizar el punto de montaje y una alternativa de lectura accesible. |
| `PracticeViewer.jsx` | Estado de pantalla, Anterior/Siguiente, índice, lectura continua y futuros controles de voz. Una instancia independiente por práctica incrustada. |
| `archive-practica` y parciales de ficha/lección | Encabezados externos, listado, vínculos y ubicación del visor; no duplicar la lógica de navegación. |
| App futura | Consumir el mismo contrato de datos con una vista nativa. Blade y React DOM pertenecen a la web. |

El componente tiene el mismo contenido en ambos contextos. Las diferencias de anchura son responsive; `context` controla opciones como abrir la ficha completa, no una copia del contenido. Si una lección contiene varias prácticas, cada instancia tendrá un ID de DOM distinto y su propio estado. Al pasar del incrustado a la ficha se podrá transferir `screenId` para continuar en el mismo punto; persistir progreso entre sesiones queda pendiente de decisión y no se confunde con completar una lección.

## Contrato de contenido a definir antes de programar

Propuesta: documento versionado con `practiceKey`, `contentVersion` y `screens[]`. Cada pantalla tiene `screenId` estable, `kind` (`heading` o `paragraph`), `headingPath[]`, contenido y referencia al bloque editorial de origen. Los títulos no se concatenan como identidad; un cambio de orden o numeración no debería romper todas las posiciones guardadas. La versión debe cambiar si cambia el contenido al que apunta una posición.

La secuencia se prepara desde la adaptación editorial aprobada. No derivarla analizando el DOM, la longitud visual del texto ni el HTML anidado de WordPress en cada visita. El preparador actual aún exporta las viñetas del TXT; el contrato de adaptación Markdown/pantallas y su importación son trabajo posterior, explícito y validado. La voz, si se aprueba, tendrá metadatos de audio por pantalla y versión, sin activar reproducción ni avance automáticos.

## Accesibilidad y estados

Botones principales de 48 px de alto, etiquetas textuales, orden de tabulación y foco visibles. La ruta visual no exige varios H1: su semántica se adapta al contexto de página o lección. Anunciar cambios de pantalla de forma breve, sin releer todo el documento automáticamente. Anterior queda desactivado al inicio; al final se ofrece volver al índice o repetir, sin afirmar que leer equivale a completar una transformación o una lección.

Prever carga, error con reintento, ausencia de prácticas, resultados vacíos y acceso sin membresía. El componente no debe incluir contenido protegido en el HTML o JSON entregado a un visitante no autorizado. Los controles de voz no se muestran hasta disponer del audio. Índice y lectura continua deben funcionar también sin voz.

## Inventario de layouts

| Archivo portable | Contenido |
| --- | --- |
| [01 · Catálogo escritorio](wireframes/01-catalogo-escritorio.svg) | Listado y filtros. |
| [02 · Práctica escritorio](wireframes/02-practica-escritorio.svg) | Índice lateral, visor y lecciones relacionadas. |
| [03 · Incrustado escritorio](wireframes/03-practica-en-leccion-escritorio.svg) | Visor dentro de la lección. |
| [04 · Catálogo móvil](wireframes/04-catalogo-movil.svg) | Listado en una columna. |
| [05 · Práctica móvil](wireframes/05-practica-movil.svg) | Cabecera 1.1 con ruta completa. |
| [06 · Incrustado móvil](wireframes/06-practica-en-leccion-movil.svg) | Lección y visor desplazables. |
| [07 · Párrafo 1](wireframes/07-parrafo-1.svg) | Párrafo completo y altura adaptada. |
| [08 · Párrafo 2](wireframes/08-parrafo-2.svg) | Segundo párrafo del mismo bloque. |
| [09 · Cabecera 1.2](wireframes/09-cabecera-1-2.svg) | Padre repetido con siguiente hijo. |
| [90 · Componentes](wireframes/90-componentes.svg) | Dos componentes de anchura usados por las cuatro vistas individuales principales. |
| [91 · Criterios](wireframes/91-criterios.svg) | Ejemplo de tres niveles, arquitectura y estados. |

`wireframes/manifest.json` conserva los IDs de archivo, página y boards de Penpot. Las formas, textos y botones de Penpot son editables. Los cuatro visores de los layouts principales son instancias vinculadas a sus dos componentes de anchura; los tres estados ilustrativos del recorrido son copias independientes porque se sustituyó texto que Penpot no actualizaba correctamente al exportar las instancias. El diseño no modifica bibliotecas globales ni páginas previas.

## Verificación realizada

Se inspeccionaron exportaciones PNG de las vistas individuales, el catálogo móvil, el incrustado móvil, los cambios de cabecera y el párrafo completo, además del panel de criterios. La API no detectó elementos fuera de los 11 boards ni texto desbordado. Se comprobaron los vínculos de los cuatro componentes principales y las 17 interacciones configuradas. Esto valida estructura y aspecto del wireframe; no sustituye una prueba funcional de la futura web o app.

Los SVG son copias abiertas de los diseños; contienen referencias de tipografía incluidas por Penpot. Las capturas PNG complementarias conservan el aspecto sin depender de esas fuentes. No se ha integrado ni desplegado esta propuesta, ni cambiado certificados o configuración operativa.
