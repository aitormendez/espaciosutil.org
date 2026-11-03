# Arquitectura CDE y Frontend

## Alcance

Este documento recoge las convenciones de estilos para **todo trabajo de interfaz del proyecto**, además de la arquitectura específica del frontend del Curso de Desarrollo Espiritual: reproductor, medios, subíndice, cuestionarios e índice del curso.

## Convenciones de estilos para todo trabajo de interfaz

**Norma general explícita, acordada el 03/11/2026: Tailwind por defecto.** Se aplica a interfaces nuevas y a los cambios de UI encargados, también fuera del CDE. No es una mera descripción del visor de presentaciones ni una preferencia que el agente pueda sustituir por la suya.

1. Expresar layout, espaciado, tipografía, responsive y estados mediante utilidades Tailwind directamente en plantillas/componentes. No crear ni ampliar una hoja CSS paralela para esas mismas responsabilidades por comodidad o preferencia del agente.
2. Mantener nombres de clases completos en fuentes detectables por la compilación. Para variantes, elegir entre clases completas; no construir nombres concatenando fragmentos de datos. Verificar que las fuentes Blade/JS/componentes correspondientes entren en el escaneo del build.
3. Se permiten propiedades CSS personalizadas para valores procedentes de datos, como el token de color de una familia. La utilidad que consume esa propiedad debe seguir siendo completa y detectable; usar una variable no convierte el layout estático en una excepción de CSS separado.
4. Cada propiedad debe tener una responsabilidad de estilo clara: evitar definirla a la vez mediante utilidades y una regla CSS que las sobrescriba. No usar `@apply` simplemente para esconder las mismas utilidades fuera del HTML y eludir la prioridad acordada.
5. Conservar los estilos globales, tokens y estilos de terceros existentes. No se ordena una migración masiva de CSS ajeno al alcance del encargo. Cualquier excepción adicional a Tailwind por defecto requiere justificación y documentación explícitas, no una decisión silenciosa del agente.

### Excepción aprobada: contenido Temas del índice CDE cargado por AJAX

La carga mediante AJAX **es compatible con Tailwind**. Aitor elige mantener CSS separado y centralizado para ese contenido porque resulta más fácil de mantener, no por una limitación técnica del framework.

| Zona del índice | Responsabilidad de estilos |
| --- | --- |
| Hero, título y cabeceras de columnas | Utilidades Tailwind en la plantilla |
| Bandas familiares, símbolos y rótulos | Utilidades Tailwind; tokens/datos mediante propiedades personalizadas cuando proceda |
| Listas y acordeones de series/bloques, incluidos sus estados | Utilidades Tailwind en los componentes/plantillas estáticos |
| Encabezados y layout del contenedor de cada panel de Temas | Utilidades Tailwind; no quedan incluidos en la excepción sólo por alojar contenido AJAX |
| Contenido cargado dentro del panel de Temas: árbol de lecciones, filas, niveles anidados, controles y sus estados | CSS centralizado en [course-index.css](../../site/web/app/themes/sage/resources/css/layouts/course-index.css), con selectores acotados a ese contenido |

Las clases semánticas necesarias para JS, pruebas o selectores pueden conservarse: no justifican por sí solas extraer el estilo de la estructura estática. Esta separación debe preservar diseño, accesibilidad y comportamiento. La declaración de la norma no acredita por sí sola que la refactorización ya esté terminada ni que su equivalencia visual/funcional esté verificada; el cierre debe apoyarse en pruebas del cambio.

## Modalidad de producción

Consultar [el contrato de modalidad CDE](cde-tipo-produccion.md) para el campo ACF obligatorio `tipo_produccion`, sus valores Voz humana/Voz sintética y el transporte desde NocoDB. Es independiente del selector vídeo/audio y de la familia de la serie. La cabecera aprobada el 03/11/2026 integra una franja con serie/familia y modalidad. Ese mismo día Aitor autorizó rediseñar el índice por bandas familiares directamente en código local, abandonando OpenPencil por sus bugs; esa entrega de estructura no añade todavía indicadores de voz a las filas del índice. La implementación local no equivale a despliegue ni a clasificación de datos históricos.

## Prácticas y composición de estudio

Consultar [Prácticas CDE](practicas-cde.md) para el CPT, la pestaña ACF, el visor compartido, la voz y el índice. La composición de resumen, práctica, presentación opcional y cuestionario usa `lesson-study-content`: sólo se renderizan bloques con contenido y cada frontera recibe un único separador. La presentación se incluye opcionalmente; su implementación no pertenece a la rama de prácticas. El estado, las pruebas y las restricciones de integración se mantienen en esa referencia especializada.

## Reproductor y medios

- El reproductor del curso se resuelve con componentes React/JSX, no con JS imperativo en Blade.
- El vídeo principal usa `@vidstack/react`.
- La miniatura se obtiene de los metadatos de Bunny (`thumbnailUrl` o `thumbnailFileName`): puede llamarse `thumbnail.jpg` o tener un sufijo propio. No asumir que todas las imágenes subidas usan el mismo nombre.
- La protección local `espaciosutil-development-isolation.php` permite únicamente GET de metadatos en `https://video.bunnycdn.com/library/457097/videos/{UUID}`, sin redirecciones ni parámetros. Mantiene bloqueados el correo, los pagos y el resto de conexiones salientes. Las imágenes se descargan directamente desde el navegador; no se usa la imagen destacada de WordPress como alternativa.
- El montaje del player se activa sobre cualquier contenedor con `data-media-props`, no solo en `single-cde`.
- `App\View\Composers\Concerns\InteractsWithCdeMedia` centraliza la preparación de `featured_media` y capítulos para reutilizarla entre vistas.

## Selector vídeo/audio

- El grupo de campos `Cde` incluye pares `featured_audio_*` para soportar audio alternativo.
- `App\View\Composers\SingleCde` expone `featured_media`.
- `App\View\Composers\TemplateCurso` puede exponer `program_guide_lesson` para renderizar una lección guía dentro de `template-programa`.
- `resources/views/partials/content-single-cde.blade.php` serializa props en `data-media-props`.
- `resources/views/partials/programa/guide-video.blade.php` reutiliza el mismo contrato para insertar el vídeo guía en “El curso en profundidad”.
- `resources/js/initFeaturedVideoPlayer.jsx` monta el wrapper React.
- `FeaturedLessonMedia.jsx` decide si mostrar vídeo, audio o ambos.
- `FeaturedVideo.jsx` y `FeaturedAudio.jsx` comparten resolución de metadatos y capítulos.
- El progreso de reproducción se persiste por Stream ID.

## Estado de lección vista

- El botón de la cabecera y los iconos del índice comparten `resources/js/courses/mark-complete.js` y `POST /wp-json/cde/v1/complete/`; el estado se guarda en `cde_completed_lessons` del usuario autenticado.
- Los iconos de las lecciones activas son botones con etiqueta, `aria-pressed`, foco visible y área de 44 × 44 px. Las lecciones inactivas y los visitantes anónimos no muestran controles de marcado.
- El guardado confirma la respuesta del servidor antes de cambiar la interfaz, bloquea clics repetidos y serializa las escrituras de esta página para no sobrescribir la lista del usuario. Si falla, conserva el estado y muestra un aviso. La delegación de eventos cubre filas AJAX; la navegación Barba vuelve a sincronizar los botones.
- En la página de una lección, los reproductores de vídeo y audio reciben `lessonId` e `isCompleted`. Comparten `useLessonCompletion.js` y `mediaCompletion.js`: se marca al avanzar en reproducción por el 95 % de la duración útil, calculada como `(duración − 36 segundos) × 0,95`. Para clips de 36 segundos o menos se usa el 95 % de la duración total.
- El criterio usa la posición de reproducción; no mide cobertura acumulada ni acredita que se haya reproducido cada fragmento. Restaurar una posición guardada o buscar con la barra no basta: debe haber avance de reproducción posterior. Los medios sin identificador de lección autenticada no envían marcado automático.
- El desmarcado manual se respeta mientras continúa la reproducción actual por el tramo final. El marcado automático se habilita de nuevo al iniciar o reanudar la reproducción, o al retroceder antes del umbral mientras se reproduce; pausar, buscar en pausa o recuperarse de buffering no basta. Esto permite volver a marcar una lección al verla o escucharla de nuevo, incluso sin recargar la página. El marcado automático nunca desmarca; tras un error espera 30 segundos antes de reintentar durante la reproducción.
- Pruebas: `node --test resources/js/courses/mark-complete.test.js resources/js/lessons/mediaCompletion.test.js` desde el tema, además de `npm run build`. Para la validación completa se requiere un WordPress accesible y una sesión de usuario.

## Subíndice jerárquico

- `app/Fields/Cde.php` define el repeater `lesson_subindex_items`.
- Cada fila almacena nivel, título, descripción, `timecode` y ancla.
- `SingleCde` expone:
  - `lesson_subindex['items']` para render de árbol
  - `lesson_subindex['chapters']` para la pista de capítulos
- El parcial `lesson-subindex` separa dos capacidades:
  - `link_titles` para decidir si el título se renderiza como enlace al ancla
  - `enable_seek_buttons` para decidir si se muestran botones `data-video-seek`
- En `single-cde` se usan ambas interacciones; en `template-programa` se desactivan los enlaces de título y se mantienen los timestamps.
- El reproductor genera una pista WebVTT de capítulos a partir de esos datos.

## Cuestionario por lección

- Los campos viven en la pestaña `Cuestionario` del grupo `Lección CDE`.
- Puede activarse por lección y soporta importación rápida desde JSON.
- `ThemeServiceProvider::importLessonQuizFromJson()` procesa la importación en `acf/save_post`.
- `SingleCde` expone `lesson_quiz` solo si el cuestionario está activado y es válido.
- `resources/js/lessons/quiz.js` monta el quiz con Swiper.
- El estado del resultado se persiste por usuario y lección vía REST:
  - `POST /wp-json/cde/v1/quiz/submit`
  - `GET /wp-json/cde/v1/quiz/result`

## Índice del curso y bloques

- La taxonomía `serie_cde` se usa jerárquicamente:
  - términos raíz = series
  - términos hijo = bloques
- [TemplateCurso](../../site/web/app/themes/sage/app/View/Composers/TemplateCurso.php) conserva `series_cde_lessons` y añade `course_family_groups`, reutilizando una única consulta de series/bloques.
- [CdeCourseFamilies](../../site/web/app/themes/sage/app/Support/CdeCourseFamilies.php) agrupa mediante `Families::resolveSeries()`, nunca por nombres, códigos o posición en el boceto. Orden de bandas: esenciales, principales, espirituales, complementarias; omite las vacías y mantiene el orden relativo de las series y sus bloques.
- Las series no resolubles se conservan al final en «Sin familia asignada», sin símbolo inventado ni clasificación por defecto. Guía de estudio no se convierte en esencial por aparecer así en el boceto. En la primera prueba del rediseño, la BD local tenía nueve raíces, sólo Urantia clasificada y ocho sin asignación; es una observación histórica, no un inventario vigente. No se sincronizó ni escribió contenido para esa prueba.
- [La plantilla del índice](../../site/web/app/themes/sage/resources/views/template-curso.blade.php) y [el parcial de familia](../../site/web/app/themes/sage/resources/views/partials/course-family-section.blade.php) renderizan título/columnas sobre la franja de constelaciones y, debajo, bandas con fondo totalmente opaco, símbolo, series y panel propio de temas. Reutilizan el canvas global; no duplican el motor de partículas ni alteran otras páginas.
- La separación de estilos acordada para la refactorización usa **utilidades Tailwind en las plantillas para la estructura estática** —hero, bandas, series/bloques, encabezados y layout de paneles— y reserva [course-index.css](../../site/web/app/themes/sage/resources/css/layouts/course-index.css) **sólo para el contenido de Temas cargado por AJAX**, según la excepción anterior. Debe conservar los cuatro tokens familiares, Rubik y la alineación de las tres columnas con Series/Temas; en móvil se apilan identidad, series y temas. Sobre el verde complementario el texto y símbolo son negros para mantener contraste; en las otras bandas, blancos.
- [El controlador del índice](../../site/web/app/themes/sage/resources/js/courses/course-index.js) mantiene estado, selección y peticiones independientes por familia. Cada familia admite una serie abierta; cambiar de bloque cancela la lectura anterior y descarta respuestas tardías. Tras un error se puede reintentar el mismo bloque. No cambia el endpoint GET ni el guardado de lecciones vistas.
- Se conservan árboles anidados, botones nativos, focos y relaciones ARIA únicas; los descendientes cerrados son inertes. El desplazamiento móvil va al panel de su propia familia. Movimiento reducido evita animaciones de acordeón y desplazamiento suave.
- El módulo limpia sus peticiones/handlers al salir mediante el hook de Barba, y su inicialización es idempotente. La limpieza/transiciones se cubren con pruebas JS; en la navegación local índice↔hub observada el documento se recargó, por lo que no se presenta ese recorrido como evidencia de transición suave.

### Validación local del rediseño, 03/11/2026

**Evidencia del rediseño previo a la refactorización Tailwind/AJAX:** se conservan las pruebas y capturas siguientes como historial. No acreditan por sí solas la equivalencia de la nueva separación de estilos; su validación debe añadirse al cerrar esa refactorización.

- 246 comprobaciones de agrupación con backend real y datos aislados, 185 de render Blade real y 16 pruebas JS nuevas; regresión de scroll/completions y de cabecera familiar correcta.
- Navegador real: acordeones simultáneos en dos grupos, carga AJAX de Urantia y Seth en paneles independientes, regreso al índice y lectura móvil sin escribir contenidos ni marcar lecciones vistas.
- Prueba visual de las cuatro familias con HTML Blade y datos de ejemplo, únicamente en el DOM del navegador: anchos 320/390/768/1024/1489, sin desbordamiento horizontal, fondos opacos y cuatro SVG existentes. [Captura de prueba de las cuatro bandas](../../site/tmp/cde-index-design/indice-cuatro-familias-prueba.png); [índice con los datos locales reales](../../site/tmp/cde-index-design/indice-local-real.png). Los ejemplos no se guardan como contenido.
- Código y recursos compilados sólo en local. Sin OpenPencil, despliegue, reclasificación, datos productivos ni sincronización del publicador. El indicador de modalidad dentro de las filas del índice sigue pendiente.

```bash
php site/tests/cde-course-families-unit.php
php site/tests/cde-course-family-render.php
# Desde site/web/app/themes/sage:
node --test resources/js/courses/course-index.test.js resources/js/courses/courseIndexScroll.test.js resources/js/courses/mark-complete.test.js
npm run build
```

### Cierre de la refactorización Tailwind/AJAX, 03/11/2026

- Estructura estática migrada a utilidades completas en Blade, sin `@apply` ni hojas paralelas para hero, bandas, símbolos, series/bloques y encabezados. Las propiedades personalizadas conservan los tokens familiares y colores de estados.
- [El parcial AJAX](../../site/web/app/themes/sage/resources/views/partials/course-index-item.blade.php) utiliza clases semánticas; sus estilos están centralizados en [course-index.css](../../site/web/app/themes/sage/resources/css/layouts/course-index.css). Conserva estructura, recursión y atributos de accesibilidad. Se reproduce la cascada efectiva anterior, incluida la sangría de listas de `1.25em` dentro de `prose-cde`.
- [La presentación de los controles de completado](../../site/web/app/themes/sage/resources/js/courses/mark-complete.js) utiliza el estado `.completed` y CSS sólo para botones del índice, sin inyectar utilidades de color/ocultación sobre ellos. Los demás botones conservan sus utilidades; no cambian las peticiones ni la lógica de progreso.
- 243 comprobaciones de render Blade real y SVG instalados, incluyendo plantilla completa, AJAX anónimo y controles logueados completados/pendientes; 26 pruebas JS de índice, scroll y completions correctas. Recursos reconstruidos con Vite.
- Comparación antes/después en Ego Lite con HTML generado por Blade: cinco anchos (320/390/768/1024/1489), estados colapsado y expandido y cuatro familias más neutral. Sin diferencias geométricas ni diferencias inesperadas en los estilos medidos; sólo cambia la serialización equivalente de la rejilla de una cabecera oculta. [Informe de estilos y controles](../../site/tmp/cde-index-tailwind/validation.json).
- Verificación adicional en la página WordPress real: paneles de Urantia/Jeshua independientes, 34 filas cargadas, teclado Espacio/Enter en anidados, desplazamiento al encabezado propio en móvil y ausencia de desbordamiento en los cinco anchos. [Informe de navegador real](../../site/tmp/cde-index-tailwind/native-validation.json). Las comprobaciones visuales de completado se hicieron con fixtures, sin guardar progreso real ni iniciar una sesión de usuario.
- Sin cambios de contenidos, clasificación, BD ni producción. Recargar el índice local para cargar juntos el HTML, CSS y JavaScript reconstruidos. Los informes temporales son evidencia de esta ejecución, no sustituyen las pruebas versionadas.

## Archivos clave

- `site/web/app/themes/sage/app/Fields/Cde.php`
- `site/web/app/themes/sage/app/View/Composers/Concerns/InteractsWithCdeMedia.php`
- `site/web/app/themes/sage/app/View/Composers/SingleCde.php`
- `site/web/app/themes/sage/app/View/Composers/TemplateCurso.php`
- `site/web/app/themes/sage/resources/views/partials/content-single-cde.blade.php`
- `site/web/app/themes/sage/resources/views/partials/programa/guide-video.blade.php`
- `site/web/app/themes/sage/resources/views/partials/lesson-subindex.blade.php`
- `site/web/app/themes/sage/resources/views/template-curso.blade.php`
- `site/web/app/themes/sage/resources/views/template-programa.blade.php`
- `site/web/app/themes/sage/resources/js/initFeaturedVideoPlayer.jsx`
- `site/web/app/themes/sage/resources/js/components/FeaturedLessonMedia.jsx`
- `site/web/app/themes/sage/resources/js/components/FeaturedVideo.jsx`
- `site/web/app/themes/sage/resources/js/components/FeaturedAudio.jsx`
- `site/web/app/themes/sage/resources/js/lessons/quiz.js`
- `site/web/app/themes/sage/resources/js/courses/course-index.js`
