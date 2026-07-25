# Cuestionario CDE compacto en móvil — Plan de implementación

> **Para agentes:** SUB-SKILL REQUERIDA: usar `superpowers:executing-plans` para ejecutar este plan paso a paso.

**Objetivo:** Convertir el estado de pregunta del cuestionario CDE en una mini aplicación móvil contenida en el viewport, sin modificar el layout de escritorio.

**Arquitectura:** Blade incorporará una cabecera móvil paralela sin alterar la cabecera de escritorio. CSS aplicará toda la reestructuración bajo `@media (max-width: 767px)`. JavaScript expondrá un helper puro para resolver el estado de navegación y usará atributos `data-*` para que el CSS muestre una sola acción principal en móvil.

**Stack:** Sage 11, Blade, Tailwind CSS 4, CSS, JavaScript ES modules, Swiper 12, `node:test`, Vite.

## Restricciones globales

- Todos los cambios visuales nuevos deben limitarse a viewports inferiores a 768 px.
- En 768 px o más deben conservarse estructura, controles, espaciados y comportamiento actuales.
- En móvil no se usará ningún peso tipográfico superior a `500`.
- La pregunta y las acciones permanecerán visibles; solo la lista de respuestas tendrá scroll interno.
- Los controles táctiles medirán al menos 44 × 44 px.
- No se modificarán la API REST, el modelo de datos ni la persistencia de resultados.
- No se incluirán en estos cambios los archivos pendientes del reproductor de audio.

---

### Tarea 1: Estado comprobable de la navegación

**Archivos:**
- Crear: `site/web/app/themes/sage/resources/js/lessons/quizState.js`
- Crear: `site/web/app/themes/sage/resources/js/lessons/quizState.test.js`
- Modificar: `site/web/app/themes/sage/resources/js/lessons/quiz.js`

**Interfaces:**
- Produce: `getQuizNavigationState(activeIndex: number, totalQuestions: number): { isFirst: boolean, isLast: boolean, previousDisabled: boolean, showValidate: boolean, showSubmit: boolean }`.
- Consume: `quiz.js` usará el resultado para actualizar `data-quiz-last`, el botón anterior y la visibilidad móvil contextual.

- [ ] **Paso 1: escribir primero las pruebas fallidas**

```js
import assert from 'node:assert/strict';
import test from 'node:test';

import { getQuizNavigationState } from './quizState.js';

test('desactiva anterior y mantiene validar en la primera pregunta', () => {
  assert.deepEqual(getQuizNavigationState(0, 12), {
    isFirst: true,
    isLast: false,
    previousDisabled: true,
    showValidate: true,
    showSubmit: false,
  });
});

test('reserva finalizar para la última pregunta', () => {
  assert.deepEqual(getQuizNavigationState(11, 12), {
    isFirst: false,
    isLast: true,
    previousDisabled: false,
    showValidate: false,
    showSubmit: true,
  });
});
```

- [ ] **Paso 2: ejecutar las pruebas y comprobar el fallo esperado**

Ejecutar desde `site/web/app/themes/sage`:

```bash
node --test resources/js/lessons/quizState.test.js
```

Resultado esperado: fallo `ERR_MODULE_NOT_FOUND` porque `quizState.js` aún no existe.

- [ ] **Paso 3: implementar el helper mínimo**

```js
export const getQuizNavigationState = (activeIndex, totalQuestions) => {
  const lastIndex = Math.max(Number(totalQuestions) - 1, 0);
  const normalizedIndex = Math.min(
    Math.max(Number(activeIndex) || 0, 0),
    lastIndex
  );
  const isFirst = normalizedIndex === 0;
  const isLast = normalizedIndex === lastIndex;

  return {
    isFirst,
    isLast,
    previousDisabled: isFirst,
    showValidate: !isLast,
    showSubmit: isLast,
  };
};
```

- [ ] **Paso 4: integrar el estado sin alterar escritorio**

Importar `getQuizNavigationState` en `quiz.js` y crear `updateQuizNavigation(root, activeIndex, totalQuestions)`. Esta función debe:

```js
const updateQuizNavigation = (root, activeIndex, totalQuestions) => {
  const state = getQuizNavigationState(activeIndex, totalQuestions);
  const previousButton = root.querySelector('.quiz-prev');

  root.dataset.quizLast = state.isLast ? 'true' : 'false';
  if (previousButton) previousButton.disabled = state.previousDisabled;

  return state;
};
```

Llamarla al iniciar Swiper y en cada `slideChange`. Conservar la lógica existente que oculta `.quiz-validate-next` en la última diapositiva para escritorio; el atributo `data-quiz-last` gobernará solo la presentación móvil mediante CSS.

- [ ] **Paso 5: ejecutar las pruebas verdes**

```bash
node --test resources/js/lessons/quizState.test.js resources/js/lessons/subindex.test.js
```

Resultado esperado: cinco pruebas superadas, cero fallos.

---

### Tarea 2: Estructura móvil y zona desplazable

**Archivos:**
- Modificar: `site/web/app/themes/sage/resources/views/partials/content-single-cde.blade.php`
- Modificar: `site/web/app/themes/sage/resources/css/layouts/quiz.css`
- Modificar: `site/web/app/themes/sage/resources/js/lessons/quiz.js`

**Interfaces:**
- Consume: `data-quiz-state="questions|summary"` y `data-quiz-last="true|false"` actualizados por `quiz.js`.
- Produce: `.quiz-mobile-status`, `.quiz-desktop-header`, `.quiz-navigation` y `.quiz-actions` como puntos de estilo estables.

- [ ] **Paso 1: separar las cabeceras móvil y escritorio en Blade**

Mantener intacto el contenido de la cabecera actual dentro de `.quiz-desktop-header`. Añadir antes una cabecera `.quiz-mobile-status` con dos copias de los datos del contador:

```blade
<header class="quiz-mobile-status" aria-label="Progreso del cuestionario">
  <span>Cuestionario</span>
  <span class="quiz-mobile-counter">
    Pregunta <span data-quiz-counter-current>1</span> de
    <span data-quiz-counter-total>0</span>
  </span>
</header>
<header class="quiz-desktop-header mb-4 text-center">
  {{-- contenido de escritorio existente, sin cambios --}}
</header>
```

Añadir `quiz-navigation` al grupo de anterior/siguiente y `quiz-actions` al grupo de finalizar/reiniciar. Añadir `aria-label="Pregunta anterior"` al botón anterior.

- [ ] **Paso 2: actualizar todas las copias del contador**

Cambiar `updateQuizCounter` para usar `querySelectorAll` y actualizar tanto la cabecera móvil como el círculo de escritorio:

```js
root.querySelectorAll('[data-quiz-counter-current]').forEach((element) => {
  element.textContent = String(current);
});
root.querySelectorAll('[data-quiz-counter-total]').forEach((element) => {
  element.textContent = String(total);
});
```

Asignar `data-quiz-state="questions"` al iniciar y reiniciar; asignar `data-quiz-state="summary"` en `goToSummary`.

- [ ] **Paso 3: añadir el layout móvil exclusivamente bajo 768 px**

En `quiz.css`, conservar todas las reglas existentes y añadir `@media (max-width: 767px)` con estas responsabilidades:

```css
@media (max-width: 767px) {
  #lesson-quiz[data-quiz-state='questions'] {
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    height: min(42rem, 100svh);
    margin-block: 0;
    padding: 0;
    border-radius: 0;
  }

  #lesson-quiz,
  #lesson-quiz * {
    font-weight: 400 !important;
  }

  #lesson-quiz .quiz-mobile-status,
  #lesson-quiz .quiz-slide h3,
  #lesson-quiz button {
    font-weight: 500 !important;
  }

  #lesson-quiz .quiz-desktop-header { display: none; }
  #lesson-quiz .quiz-mobile-status {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    padding: 0.8125rem 0.9375rem 0.5625rem;
    color: var(--color-gris2);
    font-size: 0.6875rem;
    letter-spacing: 0.12em;
    text-transform: uppercase;
  }

  #lesson-quiz .quiz-mobile-counter {
    color: var(--color-sol);
    letter-spacing: 0;
  }

  #lesson-quiz[data-quiz-state='questions'] [data-quiz-target],
  #lesson-quiz[data-quiz-state='questions'] .quiz-shell,
  #lesson-quiz[data-quiz-state='questions'] .quiz-swiper,
  #lesson-quiz[data-quiz-state='questions'] .swiper-wrapper,
  #lesson-quiz[data-quiz-state='questions'] .quiz-slide {
    min-height: 0;
  }

  #lesson-quiz[data-quiz-state='questions'] [data-quiz-target] { flex: 1 1 auto; }
  #lesson-quiz[data-quiz-state='questions'] .quiz-shell { height: 100%; gap: 0; }
  #lesson-quiz[data-quiz-state='questions'] .quiz-progress {
    flex: 0 0 auto;
    height: 4px;
    margin: 0 0.9375rem;
  }
  #lesson-quiz[data-quiz-state='questions'] .quiz-swiper { flex: 1 1 auto; }
  #lesson-quiz[data-quiz-state='questions'] .swiper-wrapper { height: 100%; }
  #lesson-quiz[data-quiz-state='questions'] .quiz-slide {
    display: flex;
    height: 100%;
    flex-direction: column;
    padding: 0;
    overflow: hidden;
  }
  #lesson-quiz[data-quiz-state='questions'] .quiz-slide h3 {
    flex: 0 0 auto;
    margin: 0;
    padding: 0.6875rem 0.9375rem 0.5625rem;
    font-size: 1.0625rem;
    line-height: 1.32;
  }
  #lesson-quiz[data-quiz-state='questions'] .quiz-options {
    min-height: 0;
    margin: 0;
    padding: 0.25rem 0.9375rem 0.75rem;
    gap: 0.4375rem;
    overflow-y: auto;
    overscroll-behavior: contain;
  }
  #lesson-quiz[data-quiz-state='questions'] .quiz-option {
    gap: 0.5rem;
    padding: 0.5625rem 0.625rem;
    font-size: 0.9375rem;
    line-height: 1.34;
  }
  #lesson-quiz .quiz-pagination,
  #lesson-quiz .quiz-next,
  #lesson-quiz .quiz-restart { display: none; }
  #lesson-quiz .quiz-footer {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 0.9375rem;
    border-top: 1px solid rgb(255 255 255 / 13%);
  }
  #lesson-quiz .quiz-navigation,
  #lesson-quiz .quiz-actions { display: contents; }
  #lesson-quiz .quiz-prev,
  #lesson-quiz .quiz-validate-next,
  #lesson-quiz .quiz-submit { min-height: 44px; }
  #lesson-quiz .quiz-prev { min-width: 44px; padding: 0.5rem; }
  #lesson-quiz .quiz-validate-next,
  #lesson-quiz .quiz-submit { flex: 1 1 auto; border-radius: 0.3125rem; }
  #lesson-quiz .quiz-submit { display: none; }
  #lesson-quiz[data-quiz-last='true'] .quiz-submit { display: inline-flex; }
}
```

Definir `.quiz-mobile-status { display: none; }` fuera del media query. La regla móvil no debe redefinir ninguna propiedad de escritorio fuera del media query.

- [ ] **Paso 4: conservar el flujo natural del resumen**

Confirmar que la altura limitada solo se aplica a `[data-quiz-state='questions']`. En `summary`, `.quiz-mobile-status` puede ocultarse mediante el estado y el resultado debe continuar en el flujo vertical existente.

- [ ] **Paso 5: ejecutar pruebas y build**

```bash
node --test resources/js/lessons/quizState.test.js resources/js/lessons/subindex.test.js
npm run build
```

Resultado esperado: cinco pruebas superadas y build Vite con código de salida 0.

---

### Tarea 3: Validación responsive y de regresión de escritorio

**Archivos:**
- Verificar: `site/web/app/themes/sage/resources/views/partials/content-single-cde.blade.php`
- Verificar: `site/web/app/themes/sage/resources/css/layouts/quiz.css`
- Verificar: `site/web/app/themes/sage/resources/js/lessons/quiz.js`

**Interfaces:**
- Consume: el layout completo producido por las tareas anteriores.
- Produce: evidencia de comportamiento móvil y ausencia de regresión en escritorio.

- [ ] **Paso 1: comprobar el estado de pregunta en 320 × 568 px**

Verificar en navegador que el bloque mide como máximo `100svh`, no provoca overflow horizontal, la pregunta y el pie permanecen visibles y `.quiz-options` es la única zona con `overflow-y: auto`.

- [ ] **Paso 2: comprobar 390 × 844 y 767 px**

Verificar que el componente no supera 42 rem en pantallas altas, que los textos no usan pesos superiores a 500 y que anterior/validar/finalizar conservan selecciones.

- [ ] **Paso 3: comprobar el límite en 768 px y escritorio ancho**

Comparar el DOM visible y estilos calculados a 768 px y 1280 px: debe mostrarse la cabecera completa, el contador circular, los puntos, anterior/siguiente, finalizar y reiniciar con el layout anterior.

- [ ] **Paso 4: ejecutar la verificación final desde cero**

```bash
node --test resources/js/components/featuredAudioLayout.test.js resources/js/lessons/quizState.test.js resources/js/lessons/subindex.test.js
npm run build
git diff --check
```

Resultado esperado: todas las pruebas superadas, build con salida 0 y ningún error de whitespace. Revisar `git diff --name-only` para confirmar que los archivos de audio pendientes no se han incorporado al alcance del cuestionario.
