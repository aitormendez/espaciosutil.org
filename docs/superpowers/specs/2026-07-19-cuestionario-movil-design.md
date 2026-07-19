# Diseño compacto del cuestionario CDE en móvil

## Objetivo

Reorganizar el estado de pregunta del cuestionario de cada lección para que la pregunta, las respuestas y las acciones permanezcan dentro de la altura visible de un teléfono. El diseño de escritorio no debe cambiar.

## Alcance

El cambio afecta al cuestionario renderizado por `partials/content-single-cde.blade.php`, a sus estilos en `resources/css/layouts/quiz.css` y al control de estados en `resources/js/lessons/quiz.js`.

Quedan fuera de alcance la estructura de datos de las preguntas, la API REST, la persistencia de resultados y el diseño de escritorio.

## Estructura móvil aprobada

En pantallas inferiores al breakpoint `md` (768 px), el cuestionario se comportará como una mini aplicación vertical:

1. Una cabecera compacta mostrará «Cuestionario» a la izquierda y «Pregunta X de N» a la derecha.
2. Una barra fina bajo la cabecera indicará el progreso.
3. La pregunta permanecerá visible y no participará en el scroll interno.
4. La lista de respuestas ocupará el espacio restante y será la única zona desplazable durante una pregunta.
5. Un pie fijo dentro del componente contendrá la navegación anterior y una única acción principal contextual.

El componente activo tendrá una altura de `min(42rem, 100svh)`. De este modo cabe en viewports bajos y no crece innecesariamente en teléfonos altos. El padding móvil se reducirá a 15–16 px y se eliminará el redondeo exterior para aprovechar el ancho disponible.

## Simplificación visual

En móvil se ocultarán:

- el título «Refuerza lo aprendido»;
- la explicación introductoria;
- el contador circular;
- la paginación por puntos;
- el botón de avance independiente;
- el botón «Reiniciar» durante el estado de preguntas.

Estos elementos se conservarán sin cambios en escritorio. «Reiniciar» seguirá disponible en el resumen final.

## Acciones y comportamiento

- En la primera pregunta, la acción de retroceso estará deshabilitada.
- En las preguntas intermedias se mostrarán «Anterior» y «Validar y continuar».
- En la última pregunta, la acción principal será «Finalizar».
- «Validar y continuar» exigirá al menos una opción seleccionada antes de avanzar, igual que ahora.
- «Finalizar» conservará la evaluación completa y el guardado mediante la API existentes.
- Las selecciones se conservarán al retroceder y avanzar.

La lógica decidirá qué acción principal mostrar según el índice activo, evitando que «Finalizar» aparezca antes de la última pregunta.

## Tipografía y controles

En el layout móvil no se usará ningún peso superior a `500`:

- pregunta, estado y botones: `font-weight: 500`;
- respuestas y textos auxiliares: `font-weight: 400`.

La pregunta tendrá aproximadamente 17–18 px y las respuestas 15–16 px, sin reducir la legibilidad para ganar espacio. Los controles interactivos mantendrán un área mínima de 44 × 44 px y estados visibles de foco.

## Estados especiales

- Una pregunta con respuestas largas conservará la pregunta y el pie visibles mientras permite desplazar solo las respuestas.
- El estado de carga y los mensajes de error seguirán apareciendo dentro del área de contenido.
- El resumen final mantendrá el flujo de lectura existente; la restricción de altura corresponde al estado interactivo de pregunta.
- En viewports de 768 px o más se conservarán el layout y los controles actuales.

## Validación

La implementación deberá comprobarse como mínimo en:

- 320 × 568 px;
- 390 × 844 px;
- 767 px de ancho;
- 768 px de ancho para verificar que el escritorio no cambia.

En móvil se verificará que no exista overflow horizontal, que solo la lista de respuestas tenga scroll interno, que pregunta y acciones permanezcan visibles, que todos los controles sean accesibles por teclado y que el flujo anterior/validar/finalizar conserve las selecciones.
