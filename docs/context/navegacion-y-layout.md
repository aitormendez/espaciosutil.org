# Navegación y Layout

## Alcance

Documento de referencia para navegación global, contextos ES/CDE, color de sección, transiciones Barba y estado visual del menú.

## Contextos de navegación

- La URL es la fuente de verdad del contexto.
- Si la URL pertenece al ámbito CDE, se usa `cde_navigation`.
- Si la URL pertenece al ámbito ES, se usa `primary_navigation`.
- El cambio de contexto se hace desde el menú principal mediante el ítem de cruce con clase `switch`.

## Color de fondo por sección

- El color de sección se obtiene del ítem top-level activo del menú actual.
- El color se sigue editando vía ACF en `menu_item_bg_color`.
- El helper `current_navigation_section_context()` resuelve:
  - `key`
  - `color`
  - `menu_item_id`
  - `label`
- El layout expone esa resolución en el `<body>` mediante `data-section` y `data-section-color`.

## Comportamiento en frontend

- `setBgColorAtLoadPage()` aplica el color persistente desde `body[data-section-color]`.
- Los submenús pueden aplicar un preview temporal del color de sección.
- Si no hay navegación final, el fondo se restaura al color persistente.

## Integración con Barba

- Se mantiene Barba en navegación interna del mismo contexto.
- Se evita Barba en rutas sensibles:
  - login
  - cuenta
  - checkout/confirmación PMPro
  - admin
- Tras cada transición se sincronizan clases del `<body>`, `data-section`, `data-section-color` y el estado activo del menú.

## Estado activo del menú

- Como la cabecera queda fuera del contenedor Barba, el estado activo no se refresca desde Blade tras cada transición.
- `resources/js/nav.js` sincroniza:
  - clases `active`
  - clases `active-ancestor`
  - línea visual desktop (`#linea`)

## Estilo por contexto

- Los colores de enlace de menú cambian según `body[data-nav-context]`.
- ES usa `--color-blanco` con énfasis `--color-morado3`.
- CDE usa `--color-cde-light` con énfasis `--color-sol`.
- También hay reglas específicas para submenús y enlaces de marca.

## Archivos clave

- `site/web/app/themes/sage/app/helpers.php`
- `site/web/app/themes/sage/app/Fields/MenuItems.php`
- `site/web/app/themes/sage/resources/views/layouts/app.blade.php`
- `site/web/app/themes/sage/resources/views/components/navigation.blade.php`
- `site/web/app/themes/sage/resources/js/nav.js`
- `site/web/app/themes/sage/resources/js/barba.js`
- `site/web/app/themes/sage/resources/css/commons/navigation.css`
