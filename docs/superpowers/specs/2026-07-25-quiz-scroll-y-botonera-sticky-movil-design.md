# Cuestionario CDE: scroll y botonera móvil

## Objetivo

Restablecer el desplazamiento natural de la página en el cuestionario de las lecciones CDE para móviles y mantener siempre disponible la acción para validar o finalizar una pregunta.

## Problema actual

En el estado de preguntas, el cuestionario móvil ocupa como máximo la altura del viewport y corta el contenido que excede esa altura. Las respuestas disponen de un scroll interno que no transmite el gesto de arrastre a la página. Cuando el cuestionario llena la pantalla, no existe una zona desde la que el usuario pueda desplazar el documento y puede perder el acceso a la botonera.

## Diseño aprobado

### Scroll del contenido

- Sólo bajo el breakpoint `md` (767 px o menos), el cuestionario deja de forzar su altura a `100svh` y de ocultar el desbordamiento del panel, del objetivo del cuestionario, de la diapositiva y de la lista de respuestas.
- La pregunta y todas sus opciones crecen dentro del flujo normal del documento. Arrastrar desde el título, una respuesta o cualquier otra parte del cuestionario desplaza la página.
- No habrá scroll interno ni `overscroll-behavior: contain` para las respuestas en este estado móvil.
- El comportamiento de escritorio y el estado de resumen permanecen sin cambios.

### Botonera persistente

- En el estado de preguntas móvil, `.quiz-footer` usa `position: sticky` y `bottom: 0` para fijarse al borde inferior del viewport mientras el cuestionario sea visible.
- Conserva los controles actuales: «Anterior» y «Validar y continuar»; en la última pregunta se sustituye la acción principal por «Finalizar».
- La botonera tendrá fondo opaco del cuestionario, borde superior y un contexto de apilamiento superior para que las opciones no se lean debajo de los botones durante el scroll.
- La botonera continúa participando en el flujo al llegar al final del cuestionario, por lo que no cubre contenido posterior de la lección.

### Pantalla completa

No se añade botón de pantalla completa nativa. No reduce la necesidad de desplazamiento para preguntas largas y la Fullscreen API no está disponible de forma uniforme en navegadores móviles. El cuestionario debe seguir siendo plenamente usable sin ella.

## Límites y validación

- No se cambia el marcado de escritorio ni las reglas de los estados de resumen.
- No se modifica el control de preguntas de Swiper ni la lógica de validación, navegación o guardado.
- Se añadirá una prueba de marcado/estilos que cubra la eliminación de la altura y scroll internos móviles y la presencia de la botonera `sticky`.
- Se verificará la compilación de Vite y el diff para asegurar que no se introducen cambios de formato o desbordamiento accidentales.
