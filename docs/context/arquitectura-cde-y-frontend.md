# Arquitectura CDE y Frontend

## Alcance

Documento de referencia para el frontend del Curso de Desarrollo Espiritual: reproductor, medios, subíndice, cuestionarios e índice del curso.

## Prácticas y composición de estudio

Consultar [Prácticas CDE](practicas-cde.md) para el CPT, la pestaña ACF, el visor compartido, la voz y el índice. La composición de resumen, práctica, presentación opcional y cuestionario usa `lesson-study-content`: sólo se renderizan bloques con contenido y cada frontera recibe un único separador. La presentación se incluye opcionalmente; su implementación no pertenece a la rama de prácticas. El estado, las pruebas y las restricciones de integración se mantienen en esa referencia especializada.

## Reproductor y medios

- El reproductor del curso se resuelve con componentes React/JSX, no con JS imperativo en Blade.
- El vídeo principal usa `@vidstack/react`.
- La miniatura correcta de Bunny.net sigue el patrón `thumbnail.jpg`, no `poster.jpg`.
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
- `App\View\Composers\TemplateCurso` expone `series_cde_lessons`.
- `template-curso.blade.php` renderiza el acordeón accesible.
- `resources/js/courses/course-index.js` controla expansión, colapso y carga AJAX del índice detallado.
- El árbol renderizado soporta nodos anidados y botones independientes para expandir descendientes.

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
