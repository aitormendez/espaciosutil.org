# Plan de acción: reestructuración de navegación ES/CDE

Fecha: 2026-03-01
Estado: Propuesto para ejecución progresiva

## 1. Objetivo

Eliminar la colisión entre navegaciones de Espacio Sutil (ES) y CDE, manteniendo un único sitio WordPress, con una experiencia clara en desktop y móvil.

Regla principal:
- La URL es la fuente de verdad del contexto.
- Si la URL es de CDE, se muestra navegación principal CDE.
- Si la URL es de ES, se muestra navegación principal ES.

## 2. Principios de diseño

- Un solo menú principal visible por pantalla.
- El cambio de contexto siempre implica navegación a la portada del contexto destino.
- El switch global y el ítem de cruce en menú deben ejecutar la misma acción.
- Evitar reglas basadas en IDs hardcodeados de páginas.
- Mantener accesibilidad (`aria-current`, foco teclado, orden de lectura).

## 3. Inventario y matriz detallada (documento separado)

Para no sobrecargar este plan, el inventario completo y la matriz extendida están en:

- [matriz_contexto_e_inventario.md](/Users/aitor/Documents/Sites/espaciosutil.org/docs/matriz_contexto_e_inventario.md)

Incluye:
- locations y menús actuales (`primary`, `cde`, `membresia`),
- árbol del menú principal ES,
- matriz URL -> contexto (CDE/ES) ampliada,
- listados completos de páginas ES, series, áreas y noticias.

## 4. Definición de contextos (base funcional)

Contexto CDE (propuesta inicial):
- Plantilla de curso (`page-template-template-curso`).
- Lecciones (`single-cde`).
- Taxonomías CDE relacionadas con el curso.
- (Opcional) rutas con prefijo CDE si se normaliza permalink.

Contexto ES:
- Todo lo demás.

Destinos canónicos:
- Home ES: `/`
- Home CDE: página principal del curso (slug actual de curso).

### 4.2 Decisiones y pendientes (actualización 2026-03-01)

Decisiones confirmadas:
- El contexto se determina por URL (sin override editorial por ahora).
- Enlace de acceso de miembros: usar `/login/` (flujo en un solo paso con PMPro).
- `Cancelar membresía` no forma parte del menú principal objetivo; se mantiene como paso interno del flujo de cuenta (`/cuenta-de-membresia/cancelacion-de-membresia/?levelstocancel=...`).

Estado de implementación (2026-03-01):
- `pmpro_login_page_id` y top bar alineados a `/login/`.

Pendiente de cierre:
- Separar `hub` y `índice de lecciones` en URLs distintas:
  - estado actual: el índice se renderiza en `/curso-de-desarrollo-espiritual/` con `template-curso`.
  - opción recomendada: reservar `/curso-de-desarrollo-espiritual/` para landing (hub) y mover índice a una URL dedicada (por ejemplo `/indice-de-lecciones/`).

### 4.1 Matriz de URLs (fuente de verdad)

La matriz operativa de Fase 0 está en el documento separado:
- [matriz_contexto_e_inventario.md](/Users/aitor/Documents/Sites/espaciosutil.org/docs/matriz_contexto_e_inventario.md)

Regla de uso:
- Esa matriz es el contrato funcional para validar qué menú aparece en cada URL antes de implementar Fase 1.

## 5. Arquitectura objetivo de menús

## Menú ES (`primary_navigation`)

- Se mantiene tal cual está actualmente.
- Se añade un ítem de cruce al contexto CDE (por ejemplo `CDE`), enlazando a la landing/hub del curso.

## Menú CDE (`cde_navigation`)

Bloque público (siempre visible):
- `Landing (hub)`
- `Índice de lecciones`
- `Suscripción`
- `El curso en profundidad` (explicación extensa)
- `Espacio Sutil` (ítem de cruce hacia home ES)

Visibilidad por autenticación:
- Usuario no logeado:
  - `Acceso` (objetivo: `/login/`)
- Usuario logeado:
  - `Mi cuenta`
  - `Perfil`
  - `Pedidos`
  - `Facturación`

Agrupación recomendada (usando jerarquía item > subitems):
- Opción A (recomendada):
  - `Cuenta` (padre visible solo logeado)
  - Hijos: `Mi cuenta`, `Perfil`, `Pedidos`, `Facturación`
  - `Acceso` visible solo no logeado
- Opción B:
  - Mostrar los 4 ítems de cuenta al mismo nivel (menos limpio en móvil).

Regla técnica:
- Controlar visibilidad por clases/metadatos de ítem (ej. `show-logged-in`, `show-logged-out`) para evitar hardcodear IDs de menu items.
- Excluir `Cancelar` del menú principal y mantenerlo en el flujo interno de cuenta.

## ¿Qué menús necesitamos crear?

Respuesta corta: para esta fase, no hace falta crear nuevas *locations*.

Reutilizamos:
- `primary_navigation` (menú `principal`) -> editar y añadir ítem de cruce `CDE`.
- `cde_navigation` (menú `cde`) -> reconstruir IA con páginas públicas + cuenta según login + ítem `Espacio Sutil`.

Se mantiene por compatibilidad:
- `membresia_navigation` (menú `membresia`) en transición, mientras migramos su uso a la nueva IA CDE.

Opcional futuro (no bloqueante):
- Crear una location separada para `account_navigation` si se quiere desacoplar totalmente cuenta de CDE.

## 6. Fases de implementación y validación

## Fase 0: Mapa de URLs y reglas

Objetivo:
- Cerrar criterios de detección de contexto antes de cambiar UI.

Tareas:
- Crear matriz de URLs representativas (home, páginas ES, curso, single-cde, taxonomías CDE, login, cuenta).
- Marcar contexto esperado por URL.

Validación:
- Revisión funcional del sitio con el responsable de contenidos.
- Aprobación explícita de la matriz (ES/CDE por URL).

Salida:
- Matriz versionada en este documento o anexo.

## Fase 0B: Contrato Barba (compatibilidad de navegación)

Objetivo:
- Evitar incoherencias de cabecera/menú al mezclar cambio de contexto con transiciones Barba.

Tareas:
- Definir el contrato de navegación:
  - Navegación dentro del mismo contexto (`es -> es`, `cde -> cde`): puede usar Barba.
  - Cambio de contexto (`es <-> cde`): navegación completa (hard reload), sin transición SPA.
- Marcar enlaces de cambio de contexto (switch top bar + ítems de cruce) con `data-barba-prevent`.
- Marcar también flujos sensibles de autenticación/membresía (login, cuenta y páginas PMPro core) con `data-barba-prevent`.
- Añadir `data-nav-context` en `<body>` para trazabilidad y verificación manual.
- Eliminar ambigüedad por IDs duplicados en navegación (`id="nav"` debe ser único).

Validación:
- Navegando entre dos URLs del mismo contexto, Barba sigue funcionando sin perder estado visual.
- Al cambiar de contexto por switch o por ítem de cruce, se produce carga completa y la cabecera refleja el contexto correcto.
- En `/login/` y páginas PMPro core, no hay efectos secundarios de transición SPA.

Salida:
- Contrato Barba aprobado y aplicado como precondición para Fase 1+.

## Fase 1: Infraestructura de contexto (sin cambios visuales)

Objetivo:
- Centralizar la lógica de contexto y destinos en backend.

Tareas:
- Añadir composer/helper que exponga:
  - `nav_context` (`es` | `cde`)
  - `primary_menu_name`
  - `switch_target_url`
  - `switch_target_label`
  - `context_cross_link_url`
  - `context_cross_link_label`
- Inyectar `data-nav-context` en layout/header.
- Definir criterio de visibilidad de ítems por autenticación sin IDs hardcodeados.

Validación:
- Inspección HTML en URLs de la matriz.
- Confirmar que no se usa array de IDs en vistas para decidir contexto.

Salida:
- Contexto confiable y reutilizable por Blade/JS.

## Fase 2: Menú principal único en cabecera (desktop primero)

Objetivo:
- Eliminar doble navegación visible simultánea ES+CDE.

Tareas:
- En `sections/header`, renderizar solo el menú del contexto activo.
- Mantener la top bar como utilitaria (switch + acceso/cuenta).
- Si la navegación CDE existe en hero, marcarla temporalmente como secundaria.

Validación:
- En páginas CDE no aparece menú ES.
- En páginas ES no aparece menú CDE.
- Estados activo/ancestro correctos.

Salida:
- Jerarquía clara en desktop.

## Fase 3: Paridad móvil (hamburguesa por contexto)

Objetivo:
- Que móvil refleje exactamente la misma lógica de contexto.

Tareas:
- Ajustar `nav.js` para no depender de una sola estructura fija.
- Hacer que hamburguesa abra/cierre el menú principal activo (ES o CDE).
- Mantener soporte de submenús cuando corresponda.

Validación:
- Pruebas manuales en 390px y 768px:
  - abrir/cerrar
  - navegar a enlaces
  - submenús
  - foco/teclado

Salida:
- Móvil consistente con desktop.

## Fase 4: Switch global con navegación explícita

Objetivo:
- Cambio de contexto claro y sin estados ambiguos.

Tareas:
- Implementar switch en top bar que navegue a la portada del otro contexto.
- Evitar persistir un “modo de menú” separado de la URL.

Validación:
- Desde URL ES -> switch lleva a home CDE y menú CDE.
- Desde URL CDE -> switch lleva a home ES y menú ES.
- Botón atrás del navegador sin incoherencias.

Salida:
- Cambio de contexto predecible.

## Fase 5: Ítem de cruce dentro del menú principal

Objetivo:
- Ofrecer segunda vía de cambio de contexto desde el propio menú.

Tareas:
- En menú ES: añadir ítem “CDE” al final.
- En menú CDE: añadir ítem “Espacio Sutil” al final.
- Ambos enlaces apuntan a la portada del otro contexto.
- Implementar en CDE la estructura pública + bloque de cuenta (según login).

Validación:
- No hay ambigüedad con el switch global.
- Se mantiene una sola navegación principal activa.

Salida:
- Redundancia útil (switch + menú) sin conflicto.

## Fase 6: Racionalización del bloque hero CDE

Objetivo:
- Evitar duplicación de funciones de navegación.

Tareas:
- Decidir si el bloque hero conserva navegación secundaria local o solo identidad visual.
- Si queda navegación secundaria, limitarla a acciones internas del área CDE.

Validación:
- Test de tareas:
  - “Ir a Contenido”
  - “Volver a ES”
  - “Encontrar Suscripción”
- Reducir pasos y tiempo de tarea respecto al estado actual.

Salida:
- Hero sin competir con el menú principal.

## Fase 7: Limpieza técnica, QA y despliegue

Objetivo:
- Cerrar deuda técnica y minimizar regresiones.

Tareas:
- Eliminar IDs duplicados (`id="nav"`).
- Eliminar condiciones legacy por IDs de página.
- Revisar accesibilidad básica (roles, `aria-expanded`, foco visible).
- Documentar arquitectura final de navegación.

Validación:
- QA cross-device (desktop/móvil/tablet).
- Checklist de regresión en URLs críticas.
- (Opcional) eventos de medición:
  - `switch_click`
  - `menu_context_link_click`

Salida:
- Navegación consolidada y mantenible.

## 7. Criterio de avance entre fases

- No se avanza de fase sin validación explícita de la fase actual.
- Si hay fallo de validación, se corrige en la misma fase.
- Cada fase debe cerrar con:
  - cambios aplicados,
  - evidencia de pruebas,
  - decisión `Go / No-Go`.

## 8. Riesgos y mitigaciones

Riesgo:
- Incoherencia entre contenido mostrado y menú activo.
Mitigación:
- URL como única fuente de verdad.

Riesgo:
- Roturas por reglas hardcodeadas de IDs.
Mitigación:
- Detección semántica por tipo de contenido/template/taxonomía/ruta.

Riesgo:
- Regresión en móvil.
Mitigación:
- Fase específica de paridad móvil antes del rollout final.

Riesgo:
- Duplicación de navegación en CDE.
Mitigación:
- Redefinir hero como secundario o puramente visual.

Riesgo:
- Cabecera desincronizada con el contexto real al navegar con Barba.
Mitigación:
- Aplicar Fase 0B (hard reload en cambios de contexto y rutas sensibles).

## 9. Orden sugerido de ejecución inmediata

1. Fase 0 (matriz de URLs)
2. Fase 0B (contrato Barba)
3. Fase 1 (composer/contexto)
4. Fase 2 (menú único desktop)
5. Fase 3 (móvil)
6. Fase 4 y 5 (switch + ítems de cruce)
7. Fase 6 y 7 (racionalización + QA + despliegue)
