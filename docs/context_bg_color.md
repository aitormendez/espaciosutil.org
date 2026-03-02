# Contexto técnico del flujo de color de fondo (`#tsparticles`)

## 1. Objetivos funcionales

El sistema actual está diseñado para cumplir estos objetivos:

1. El color de fondo debe representar la **sección real** de la página actual.
2. La sección se define por el **ítem de primer nivel** del menú principal.
3. Al abrir un submenú (sin navegar), el color puede cambiar como **vista previa temporal**.
4. Si se cierra el submenú sin cambiar de página, el color debe volver al color persistente de la página actual.
5. Debe funcionar igual con navegación normal y con navegación AJAX (Barba).

---

## 2. Fuente de verdad

La fuente de verdad del color no es el click, sino el backend.

- En cada request, PHP resuelve la sección actual y su color.
- Esa información se expone en `<body>` como:
  - `data-section`
  - `data-section-color`
- El frontend toma ese valor como estado persistente.

Esto evita inconsistencias cuando el usuario abre/cierra menús sin navegar.

---

## 3. Archivos implicados

## Backend (WordPress / PHP)

- `site/web/app/themes/sage/app/Fields/MenuItems.php`
  - Define el campo ACF `menu_item_bg_color` en ítems de menú.

- `site/web/app/themes/sage/app/helpers.php`
  - `navigation_section_context()`:
    - resuelve sección y color para la URL actual en una *menu location* dada.
  - `current_navigation_section_context()`:
    - resuelve sección y color usando el menú principal activo por contexto (`primary_navigation` o `cde_navigation`).
  - `primary_navigation_section_context()`:
    - wrapper legacy para casos que sigan necesitando forzar `primary_navigation`.
  - `menu_item_match_path()`:
    - normaliza/matchea rutas de ítems de menú, ignora `#` y hosts externos.
  - `top_level_menu_item()`:
    - sube por `menu_item_parent` hasta ancestro top-level.
  - `normalize_section_path()`:
    - estandariza rutas para comparaciones consistentes.
  - `should_prevent_barba_for_url()` y helpers de contexto:
    - marcan enlaces que no deben usar Barba (rutas sensibles o salto de contexto).

## Plantillas Blade

- `site/web/app/themes/sage/resources/views/layouts/app.blade.php`
  - Inyecta en `<body>`:
    - `data-section="..."`
    - `data-section-color="..."`
    - `data-nav-context="..."`

- `site/web/app/themes/sage/resources/views/components/navigation.blade.php`
  - Renderiza top-level links con:
    - `data-section="section-{menu_item_id}"`
    - `data-color="{menu_item_bg_color}"`
  - Añade `data-barba-prevent` en enlaces que deben excluirse de Barba.

- `site/web/app/themes/sage/resources/views/sections/header.blade.php`
  - Renderiza el contenedor de navegación y marca contexto de navegación en `#nav`.

## Frontend (JS)

- `site/web/app/themes/sage/resources/js/nav.js`
  - Controla todo el flujo de color persistente vs temporal.
  - Funciones clave:
    - `getPersistentSectionColor()`
    - `changeBgColor(color, immediate)`
    - `previewMenuColor(menu)`
    - `restorePersistentBgColor()`
    - `setBgColorAtLoadPage()`
    - `particlesBgColor()`
    - `navegacion()` (desktop)
    - `navegacionMovil()` (móvil)

- `site/web/app/themes/sage/resources/js/app.js`
  - Inicializa en `DOMContentLoaded`:
    - `particlesBgColor()`
    - `setBgColorAtLoadPage()`
    - navegación desktop/móvil.

- `site/web/app/themes/sage/resources/js/barba.js`
  - En cada transición Barba:
    - parsea `next.html`
    - sincroniza `body.className`, `body.dataset.navContext`, `body.dataset.section`, `body.dataset.sectionColor`
    - llama `setBgColorAtLoadPage()` para consolidar color persistente en AJAX.

---

## 4. Modelo de datos

## ACF

- Campo por ítem de menú: `menu_item_bg_color`.
- Ubicación: `nav_menu_item == all`.
- Valor por defecto: `#000000`.

## Data attributes (runtime)

### En `<body>`

- `data-section`: identificador lógico de sección (ej: `section-123`).
- `data-section-color`: color persistente de la sección actual.
- `data-nav-context`: contexto de navegación (`es` o `cde`).

### En links top-level del menú

- `data-section`: sección asociada al ítem top-level.
- `data-color`: color asociado al ítem top-level.

---

## 5. Algoritmo de resolución de sección (backend)

Implementado en `navigation_section_context()` y `current_navigation_section_context()`.

1. Obtiene el menú asignado a la *location* activa del contexto.
2. Carga todos sus ítems (`wp_get_nav_menu_items`).
3. Normaliza la URL actual (`REQUEST_URI` + `normalize_section_path`).
4. Recorre los ítems y calcula mejor match por ruta:
   - exact match,
   - o prefijo más largo,
   - ignora enlaces `#` y externos.
5. Si no hay match por ruta, intenta fallback por `get_queried_object_id()` contra `object_id` del menú.
6. Toma el match y sube por jerarquía (`menu_item_parent`) hasta el ancestro top-level.
7. Lee el color ACF del ancestro top-level.
8. Devuelve contexto:
   - `key` (`section-{ID}`)
   - `color`
   - `menu_item_id`
   - `label`

Fallback global si algo falla:
- `home` + `#000000`.

Nota importante:
- Aunque un top-level sea un enlace personalizado `#`, puede seguir definiendo color de sección:
  - el match lo hace un subitem navegable,
  - luego se asciende al top-level padre,
  - y se toma su color.
- En contexto CDE, el cálculo persistente usa `cde_navigation` (no `primary_navigation`), evitando que el color vuelva a negro tras recarga en páginas del curso.

---

## 6. Flujo frontend detallado

## 6.1 Carga inicial de página (full load)

1. `app.js` ejecuta `setBgColorAtLoadPage()`.
2. `setBgColorAtLoadPage()` lee `body.dataset.sectionColor`.
3. Aplica color inmediato en `#tsparticles` con `gsap.set`.

Resultado:
- El color inicial siempre coincide con la sección real de la página.

## 6.2 Apertura/cierre de submenú en desktop

En `navegacion()`:

- Al abrir un top-level con submenú:
  - `menuOpen()` llama `previewMenuColor(menu)`.
  - El fondo cambia temporalmente al color del top-level abierto.

- Al cerrar ese submenú sin navegar:
  - `menuClose()` llama `restorePersistentBgColor()`.
  - El fondo vuelve al color persistente (`body.data-section-color`).

Esto aplica también cuando se cierra por:
- click fuera del banner,
- ocultación del banner por scroll,
- cambio de top-level abierto.

## 6.3 Apertura/cierre de submenú en móvil

En `navegacionMovil()`:

- `menu.open()` hace preview temporal (`previewMenuColor`).
- `menu.close()` restaura persistente (`restorePersistentBgColor`) salvo cierres internos controlados.
- Al cerrar el nav móvil (`cerrarNav()`), si hay submenú abierto, también se restaura color.

## 6.4 Click en top-level sin submenú

`particlesBgColor()` solo enlaza listeners de color para top-level **sin** `.my-child-menu`.

Comportamiento:
- Cambia color al click (feedback inmediato).
- Luego la navegación (full o Barba) consolidará el color persistente según destino.

---

## 7. Integración con Barba (navegación AJAX)

Problema que resuelve:
- El header/nav está fuera de `data-barba="container"`, por lo que en transición no se reconstruye completamente con cada navegación.

Solución aplicada en `barba.js`:

1. En `afterLeave`, parsea `data.next.html`.
2. Extrae `<body>` del HTML destino.
3. Sincroniza:
   - clases de `<body>`
   - `dataset.navContext`
   - `dataset.section`
   - `dataset.sectionColor`
4. Ejecuta `setBgColorAtLoadPage()`.

Resultado:
- Incluso sin recarga completa, el color persistente se actualiza correctamente según la página destino.

---

## 8. Reglas de Barba prevent relacionadas

`should_prevent_barba_for_url()` evita Barba cuando:

- la ruta es sensible (login/membresía/admin), o
- hay salto entre contextos (`es` ↔ `cde`).

Esto se refleja en `data-barba-prevent` en enlaces del header/navigation.

Impacto en color:
- Si Barba se evita, hay recarga completa y el backend vuelve a inyectar `data-section-color`.
- Si Barba se permite, `barba.js` sincroniza datasets y reaplica color.

En ambos casos se mantiene coherencia del color por sección.

---

## 9. Selectores y hooks críticos

- Contenedor de fondo animado: `#tsparticles`
- Menú top-level links: `#nav .my-menu > li > a`
- Ítem de menú: `.my-menu-item`
- Submenú: `.my-child-menu`
- Header: `#banner`
- Cierre por click externo: listener global `document.addEventListener('click', ...)`

Hooks/entradas principales:
- `DOMContentLoaded` (`app.js`)
- `barba.hooks.afterLeave` (`barba.js`)

---

## 10. Fallbacks y decisiones defensivas

- Si falta `data-section-color`, se usa `#000000`.
- Si no existe `#tsparticles`, `changeBgColor()` no rompe.
- Si `next.html` en Barba no trae `<body>`, se aborta sincronización sin romper flujo.
- En resolver de sección, si no hay match, fallback a `home`.

---

## 11. Checklist rápido de validación manual

1. Entrar en una página de una sección y verificar color inicial correcto.
2. Abrir top-level con submenú:
   - color cambia en preview.
3. Cerrar submenú sin navegar:
   - color vuelve al original.
4. Abrir otro top-level con submenú:
   - preview cambia al nuevo.
5. Navegar a subitem:
   - color final coincide con sección destino.
6. Repetir en móvil (abrir/cerrar nav).
7. Probar rutas con `data-barba-prevent` (login/membresía) y confirmar consistencia.
