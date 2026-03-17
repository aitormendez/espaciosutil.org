# Arquitectura CDE y Frontend

## Alcance

Documento de referencia para el frontend del Curso de Desarrollo Espiritual: reproductor, medios, subíndice, cuestionarios e índice del curso.

## Reproductor y medios

- El reproductor del curso se resuelve con componentes React/JSX, no con JS imperativo en Blade.
- El vídeo principal usa `@vidstack/react`.
- La miniatura correcta de Bunny.net sigue el patrón `thumbnail.jpg`, no `poster.jpg`.

## Selector vídeo/audio

- El grupo de campos `Cde` incluye pares `featured_audio_*` para soportar audio alternativo.
- `App\View\Composers\SingleCde` expone `featured_media`.
- `resources/views/partials/content-single-cde.blade.php` serializa props en `data-media-props`.
- `resources/js/initFeaturedVideoPlayer.jsx` monta el wrapper React.
- `FeaturedLessonMedia.jsx` decide si mostrar vídeo, audio o ambos.
- `FeaturedVideo.jsx` y `FeaturedAudio.jsx` comparten resolución de metadatos y capítulos.
- El progreso de reproducción se persiste por Stream ID.

## Subíndice jerárquico

- `app/Fields/Cde.php` define el repeater `lesson_subindex_items`.
- Cada fila almacena nivel, título, descripción, `timecode` y ancla.
- `SingleCde` expone:
  - `lesson_subindex['items']` para render de árbol
  - `lesson_subindex['chapters']` para la pista de capítulos
- Las vistas del subíndice renderizan botones `data-video-seek`.
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
- `site/web/app/themes/sage/app/View/Composers/SingleCde.php`
- `site/web/app/themes/sage/app/View/Composers/TemplateCurso.php`
- `site/web/app/themes/sage/resources/views/partials/content-single-cde.blade.php`
- `site/web/app/themes/sage/resources/views/partials/lesson-subindex.blade.php`
- `site/web/app/themes/sage/resources/views/template-curso.blade.php`
- `site/web/app/themes/sage/resources/js/initFeaturedVideoPlayer.jsx`
- `site/web/app/themes/sage/resources/js/components/FeaturedLessonMedia.jsx`
- `site/web/app/themes/sage/resources/js/components/FeaturedVideo.jsx`
- `site/web/app/themes/sage/resources/js/components/FeaturedAudio.jsx`
- `site/web/app/themes/sage/resources/js/lessons/quiz.js`
- `site/web/app/themes/sage/resources/js/courses/course-index.js`
