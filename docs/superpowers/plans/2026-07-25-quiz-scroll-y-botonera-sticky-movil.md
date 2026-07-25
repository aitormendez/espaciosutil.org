# Cuestionario CDE: scroll y botonera móvil Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permitir el scroll normal de las preguntas del cuestionario CDE en móvil y mantener visible su botonera de validación o finalización.

**Architecture:** El cambio queda confinado a las reglas móviles del cuestionario. Se elimina la cadena de alturas y desbordamientos que convierte el panel en una superficie con scroll propio; la botonera se vuelve `sticky` dentro del flujo de la sección. La prueba existente de marcado y CSS protege tanto la accesibilidad táctil como estas reglas de disposición.

**Tech Stack:** Sage 11 Blade, CSS, Tailwind CSS para el marcado existente, Swiper y pruebas nativas de Node (`node:test`).

## Global Constraints

- Aplicar el comportamiento sólo hasta `767 px`; escritorio no cambia.
- No modificar la lógica de Swiper, validación, navegación ni guardado del cuestionario.
- No añadir pantalla completa nativa.
- No aplicar `font-bold`; los textos nuevos o modificados no superan `font-medium`.
- No incluir `site/web/app/themes/sage/package-lock.json` en los commits de esta tarea.

---

### Task 1: Proteger el flujo móvil y la botonera persistente

**Files:**
- Modify: `site/web/app/themes/sage/resources/js/lessons/quizMarkup.test.js`
- Modify: `site/web/app/themes/sage/resources/css/layouts/quiz.css:117-294`

**Interfaces:**
- Consumes: el selector de estado móvil `#lesson-quiz[data-quiz-state='questions']` y la estructura existente `.quiz-shell > .quiz-swiper + .quiz-footer`.
- Produces: una lista de respuestas que forma parte del scroll de la página y una `.quiz-footer` pegada al borde inferior mientras se recorren las preguntas móviles.

- [ ] **Step 1: Escribir la prueba que falla**

  Añadir a `quizMarkup.test.js` una función para recuperar la regla móvil del estado de preguntas y dos pruebas:

  ```js
  test('permite que las preguntas móviles crezcan en el scroll de página', () => {
    const questionStateRule = getRuleBody(
      "#lesson-quiz[data-quiz-state='questions']"
    );
    const targetRule = getRuleBody(
      "#lesson-quiz[data-quiz-state='questions'] [data-quiz-target]"
    );
    const optionsRule = getRuleBody(
      "#lesson-quiz[data-quiz-state='questions'] .quiz-options"
    );

    assert.doesNotMatch(questionStateRule, /height:\s*min\(42rem,\s*100svh\)/);
    assert.doesNotMatch(targetRule, /flex:\s*1 1 auto/);
    assert.doesNotMatch(optionsRule, /overflow-y:\s*auto/);
    assert.doesNotMatch(optionsRule, /overscroll-behavior:\s*contain/);
  });

  test('mantiene la botonera móvil pegada al borde inferior', () => {
    const footerRule = getRuleBody(
      "#lesson-quiz[data-quiz-state='questions'] .quiz-footer"
    );

    assert.match(footerRule, /position:\s*sticky/);
    assert.match(footerRule, /bottom:\s*0/);
    assert.match(footerRule, /z-index:\s*1/);
  });
  ```

- [ ] **Step 2: Ejecutar la prueba para comprobar que falla**

  Run:

  ```bash
  node --test resources/js/lessons/quizMarkup.test.js
  ```

  Expected: FAIL porque el cuestionario conserva `height: min(42rem, 100svh)`, el objetivo usa `flex: 1 1 auto`, las opciones usan scroll propio y la botonera no tiene `position: sticky`.

- [ ] **Step 3: Aplicar las reglas móviles mínimas**

  En `quiz.css`, dentro de `@media (max-width: 767px)`, realizar estas modificaciones limitadas al estado `data-quiz-state='questions'`:

  ```css
  #lesson-quiz[data-quiz-state='questions'] {
    box-sizing: border-box;
    display: block;
    margin-block: 0;
    padding: 0;
    border-radius: 0;
  }

  #lesson-quiz[data-quiz-state='questions'] [data-quiz-target] {
    display: block;
    overflow: visible;
  }

  #lesson-quiz[data-quiz-state='questions'] .quiz-shell,
  #lesson-quiz[data-quiz-state='questions'] .quiz-swiper,
  #lesson-quiz[data-quiz-state='questions'] .quiz-swiper .swiper-wrapper,
  #lesson-quiz[data-quiz-state='questions'] .quiz-slide {
    height: auto;
    min-height: 0;
  }

  #lesson-quiz[data-quiz-state='questions'] .quiz-slide {
    display: block;
    overflow: visible;
  }

  #lesson-quiz[data-quiz-state='questions'] .quiz-options {
    display: flex;
    margin: 0;
    padding: 0.25rem 0.9375rem 0.75rem;
    gap: 0.4375rem;
  }

  #lesson-quiz[data-quiz-state='questions'] .quiz-footer {
    position: sticky;
    bottom: 0;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 0.9375rem;
    border-top: 1px solid rgb(255 255 255 / 13%);
    background: rgb(75 32 73 / 98%);
  }
  ```

  Eliminar las reglas móviles que imponían `height: min(42rem, 100svh)`, `flex: 1 1 auto`, `height: 100%`, `overflow: hidden`, `overflow-y: auto` y `overscroll-behavior: contain` a los elementos anteriores. Mantener las dimensiones táctiles de las opciones, las reglas de visibilidad de los controles y los estilos de escritorio existentes.

- [ ] **Step 4: Ejecutar las pruebas focalizadas**

  Run:

  ```bash
  node --test resources/js/lessons/quizMarkup.test.js resources/js/lessons/quizState.test.js
  ```

  Expected: PASS con ocho pruebas, incluidas las dos que garantizan scroll de página y botonera `sticky`.

- [ ] **Step 5: Verificar compilación y diff**

  Run:

  ```bash
  npm run build -- --logLevel error
  git diff --check
  ```

  Expected: ambos comandos finalizan con código `0`.

- [ ] **Step 6: Comprobar manualmente el caso móvil**

  Con un viewport de `320 px` de ancho, abrir una lección que tenga al menos cuatro opciones de respuesta y verificar:

  1. El gesto vertical sobre el título y sobre cada opción desplaza la página.
  2. «Validar y continuar» permanece anclado al borde inferior durante el desplazamiento.
  3. En la última pregunta, «Finalizar» ocupa la acción principal sin que quede oculto.
  4. El resumen final y un viewport `md` o superior mantienen su composición anterior.

- [ ] **Step 7: Crear el commit de implementación**

  Run:

  ```bash
  git add site/web/app/themes/sage/resources/css/layouts/quiz.css \
    site/web/app/themes/sage/resources/js/lessons/quizMarkup.test.js
  git commit -m "fix(cde): restablece el scroll móvil del cuestionario"
  ```

  Expected: un commit que sólo incluya el CSS móvil y la prueba de cuestionario; no incluir `site/web/app/themes/sage/package-lock.json`.
