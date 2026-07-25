# Scroll móvil al cargar temas del índice CDE

## Objetivo

Tras seleccionar un bloque del índice del curso en móvil, desplazar suavemente la página hasta el encabezado «Temas» una vez que los temas se hayan cargado correctamente.

## Alcance

El comportamiento se limita a `resources/js/courses/course-index.js` y al encabezado «Temas» de `resources/views/template-curso.blade.php`.

No modifica la carga AJAX, los acordeones de series, la expansión de descendientes, el diseño de escritorio ni el comportamiento de error.

## Comportamiento

1. El encabezado «Temas» tendrá un identificador estable para que el controlador pueda localizarlo.
2. Al pulsar un bloque, el índice conservará la carga AJAX actual.
3. Solo cuando la petición responda correctamente y el HTML de temas se haya insertado, el controlador comprobará que el viewport sea inferior a 768 px.
4. En ese caso, desplazará el encabezado «Temas» al inicio visible mediante scroll suave.
5. En 768 px o más, no habrá desplazamiento automático.
6. Si la petición falla, no se ejecutará scroll.

## Accesibilidad y experiencia

El desplazamiento se activará únicamente tras una acción explícita del usuario. El encabezado conservará su semántica `h2`; no se moverá el foco ni se alterarán los atributos ARIA existentes.

## Validación

- En un viewport de 767 px, seleccionar un bloque inserta los temas y desplaza suavemente hasta «Temas».
- En un viewport de 768 px, seleccionar un bloque inserta los temas sin desplazar la página.
- Si la petición falla, se muestra el mensaje de error actual y la posición de la página no cambia.
- Las pruebas existentes del índice y el build de Vite continúan pasando.
