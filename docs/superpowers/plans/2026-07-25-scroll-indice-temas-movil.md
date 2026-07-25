# Scroll móvil del índice de temas CDE — Plan de implementación

> **Para agentes:** SUB-SKILL REQUERIDA: usar `superpowers:executing-plans` para ejecutar este plan tarea por tarea.

**Objetivo:** Tras cargar los temas de un bloque, desplazar suavemente el viewport móvil hasta el encabezado «Temas» sin afectar a escritorio.

**Arquitectura:** Un helper puro decide si procede desplazar según el breakpoint y la existencia del destino. `course-index.js` lo invoca solo después de insertar una respuesta AJAX correcta. Blade aporta un identificador estable al `h2` de destino.

**Stack:** Sage 11, Blade, JavaScript ES modules, GSAP, `node:test`, Vite.

## Restricciones globales

- El scroll automático se aplica solo por debajo de 768 px.
- El scroll se ejecuta solo tras una respuesta AJAX correcta e inserción de temas.
- En escritorio, la carga y el viewport no cambian.
- En un error de red no se cambia la posición de la página.
- No se modifica la expansión de series, bloques o descendientes.

---

### Tarea 1: Decisión comprobable de scroll y conexión con el índice

**Archivos:**
- Crear: `site/web/app/themes/sage/resources/js/courses/courseIndexScroll.js`
- Crear: `site/web/app/themes/sage/resources/js/courses/courseIndexScroll.test.js`
- Modificar: `site/web/app/themes/sage/resources/js/courses/course-index.js`
- Modificar: `site/web/app/themes/sage/resources/views/template-curso.blade.php`

**Interfaces:**
- Produce: `MOBILE_COURSE_INDEX_QUERY = '(max-width: 767px)'` y `shouldScrollToCourseTopics(isMobile: boolean, hasHeading: boolean): boolean`.
- Consume: `course-index.js` usa el helper y el elemento `#course-topics-heading` tras insertar `data.html`.

- [ ] **Paso 1: escribir las pruebas fallidas**

```js
import assert from 'node:assert/strict';
import test from 'node:test';

import {
  MOBILE_COURSE_INDEX_QUERY,
  shouldScrollToCourseTopics,
} from './courseIndexScroll.js';

test('autoriza el scroll cuando hay encabezado en móvil', () => {
  assert.equal(MOBILE_COURSE_INDEX_QUERY, '(max-width: 767px)');
  assert.equal(shouldScrollToCourseTopics(true, true), true);
});

test('evita el scroll sin encabezado o fuera de móvil', () => {
  assert.equal(shouldScrollToCourseTopics(true, false), false);
  assert.equal(shouldScrollToCourseTopics(false, true), false);
});
```

- [ ] **Paso 2: ejecutar la prueba roja**

Ejecutar desde `site/web/app/themes/sage`:

```bash
node --test resources/js/courses/courseIndexScroll.test.js
```

Resultado esperado: `ERR_MODULE_NOT_FOUND` porque el helper aún no existe.

- [ ] **Paso 3: implementar el helper mínimo**

```js
export const MOBILE_COURSE_INDEX_QUERY = '(max-width: 767px)';

export const shouldScrollToCourseTopics = (isMobile, hasHeading) =>
  Boolean(isMobile && hasHeading);
```

- [ ] **Paso 4: dar un destino estable al encabezado**

En `template-curso.blade.php`, reemplazar el encabezado actual por:

```blade
<h2 id="course-topics-heading" class="mb-6 font-sans text-2xl">Temas</h2>
```

- [ ] **Paso 5: invocar el scroll solo después de una carga correcta**

En `course-index.js`, importar el helper, resolver el encabezado una vez y añadir:

```js
const scrollToTopicsOnMobile = () => {
  const isMobile =
    typeof window !== 'undefined' &&
    window.matchMedia?.(MOBILE_COURSE_INDEX_QUERY).matches;

  if (!shouldScrollToCourseTopics(isMobile, Boolean(topicsHeading))) {
    return;
  }

  topicsHeading.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
```

Llamar a `scrollToTopicsOnMobile()` inmediatamente después de `container.innerHTML = data.html` e `initializeCourseIndexChildren(container)`, dentro de `try`. No llamarlo desde `catch` ni `finally`.

- [ ] **Paso 6: ejecutar pruebas verdes**

```bash
node --test resources/js/courses/courseIndexScroll.test.js resources/js/lessons/subindex.test.js
```

Resultado esperado: cinco pruebas superadas, cero fallos.

- [ ] **Paso 7: validación manual y build**

```bash
npm run build
git diff --check
```

En 767 px, seleccionar un bloque debe cargar los temas y desplazar suavemente hasta «Temas». En 768 px, debe cargar los temas sin desplazar la página. Un fallo de la petición debe conservar la posición actual.
