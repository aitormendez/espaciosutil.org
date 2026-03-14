# Contexto del Proyecto: Espacio Sutil (Restauración)

## 1. Resumen del Proyecto

- **Nombre:** Espacio Sutil
- **Tipo:** Sitio web profesional con WordPress.
- **Funcionalidad Principal:** Portal de membresías y cursos online (Curso de Desarrollo Espiritual).
- **Stack Tecnológico:**
  - **Trellis:** Provisión de servidores y despliegue.
  - **Bedrock:** Estructura moderna de WordPress.
  - **Sage 11:** Tema personalizado con Blade, Tailwind CSS y Vite.
  - **Plugins Clave:** Paid Memberships Pro (PMP).

## 2. Entorno de Desarrollo

- **Host:** macOS
- **Máquina Virtual:** Gestionada por **Trellis** con el proveedor **Lima**.
- **Directorio Raíz del Proyecto (Host):** `/Users/aitor/Documents/Sites/espaciosutil.org`
- **Directorio del Sitio WordPress (Host)::** `/Users/aitor/Documents/Sites/espaciosutil.org/site`

## 3. Instrucciones para Gemini

### Idioma de Respuesta

Siempre debes responder en español.

### Sintaxis Blade

Debido a que el símbolo `@` tiene un significado especial en la interfaz de `gemini-cli`, al proporcionar fragmentos de código Blade que contengan directivas (como `@if`, `@foreach`, `@include`), es recomendable omitir el símbolo `@` para evitar conflictos. Por ejemplo, en lugar de `@if`, se puede usar `if`. Por tanto, al leer fragmentos de Blade que omiten la arroba, debo interpretarlos como si tuvieran la arroba.

El mismo problema ocurra con las directivas con `@`en CSS.

### Ejecución de Comandos `wp-cli`

Para ejecutar comandos `wp-cli`, se deben seguir las siguientes reglas:

1.  **NO intentar ejecutar `wp-cli` dentro de la VM** usando `trellis exec` o `limactl`. La comunicación debe hacerse desde el sistema anfitrión (macOS).
2.  El comando `run_shell_command` debe usar una **ruta relativa** desde el directorio raíz del proyecto (donde se inicia `gemini-cli`) hasta el directorio del sitio de WordPress.
3.  La ruta relativa correcta para el parámetro `directory` es: `site`.

**Ejemplo de uso correcto:**

```python
default_api.run_shell_command(
    command="wp post list --post_type=page",
    directory="site"
)
```

### Nota sobre la Base de Datos

En el proyecto original el archivo `.env` en el directorio `site/` debía tener `DB_HOST='127.0.0.1'` para permitir la conexión a la base de datos desde el anfitrión, pero sospecho que este cambio podía estar en el origen del problema. Veremos ahora si podemos mantener la configuración por defecto de la instalación.

### Gestión de Versiones (Git)

Se ha verificado que Gemini puede ejecutar el flujo completo de Git (status, diff, add, commit, push) en este repositorio. Todos los mensajes de commit deben seguir las convenciones de [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/).

- **Mensajes de Commit:** Debido a limitaciones técnicas, los mensajes de commit deben ser de una sola línea.
- **Idioma de los commits:** Codex y cualquier otro agente deben redactar el mensaje siguiendo Conventional Commits, pero con la descripción en español. Ejemplo válido: `chore(deps): actualiza WordPress y dependencias de Composer`.

### Gestión de Archivos Vault

Antes de añadir archivos `vault.yml` al área de preparación (`git add`) o de realizar un commit que los incluya, Gemini debe verificar su estado. Si los archivos `vault.yml` tienen modificaciones y están desencriptados, Gemini debe encriptarlos utilizando el comando `trellis vault encrypt <entorno>` (por ejemplo, `trellis vault encrypt development`, `trellis vault encrypt staging`, `trellis vault encrypt production`) antes de proceder. Esto asegura que nunca se suban al repositorio desencriptados.

## 4. Estado Operativo Actual

- El entorno local está estable y operativo.
- El acceso a WordPress (`wp-admin`) funciona con normalidad.
- Los comandos `wp-cli` funcionan desde el host, ejecutándose dentro de `site/web`.
- La base de datos y el aprovisionamiento de Trellis están funcionando en el estado actual del proyecto.

## 5. Mantenimiento de Entorno

- Mantener `DB_HOST='127.0.0.1'` en `site/.env` y `db_user_host: '127.0.0.1'` en Trellis para asegurar conectividad desde el host.
- Ejecutar `wp-cli` desde `site/web` (no dentro de la VM con `trellis exec` para uso diario).
- Antes de cambios de infraestructura, preferir `trellis provision development` para mantener consistencia del entorno.

## 6. Despliegue con GitHub Actions

Los flujos de trabajo de despliegue (`deploy-staging.yml` y `deploy-production.yml`) ahora utilizan entornos de GitHub Actions (`staging` y `production` respectivamente).

Para el entorno de `production`, es necesario configurar el secreto `TRELLIS_DEPLOY_SSH_KNOWN_HOSTS` en el repositorio de GitHub. Este secreto debe contener las claves SSH conocidas del servidor de producción para permitir la conexión segura durante el despliegue.

## 7. Notas de Arquitectura y Frontend

### Reproductor de Vídeo (Bunny.net y React)

- La funcionalidad del reproductor de vídeo no se gestiona con JavaScript simple o directamente en Blade, sino a través de **componentes de React (JSX)**.
- El componente principal que controla el reproductor de vídeo de Bunny.net es `resources/js/components/FeaturedVideo.jsx`.
- Este componente utiliza la librería `@vidstack/react` para renderizar el reproductor.
- **URL del Póster:** La URL correcta para la imagen del póster (miniatura) de un vídeo en Bunny.net no es `poster.jpg`, sino `thumbnail.jpg`. La estructura es: `https://{PULL_ZONE}.b-cdn.net/{VIDEO_ID}/thumbnail.jpg`.

### Selector Video/Audio con Vidstack

- En la pestaña **Medios (Video/Audio)** del grupo “Lección CDE” (`app/Fields/Cde.php`) se incluyen los pares `featured_audio_*` (ID, library_id y nombre opcional). Si el ID está vacío, la vista ignora el modo audio.
- El composer `App\View\Composers\SingleCde` expone `featured_media`, que empaqueta la información de vídeo y audio (IDs, library, nombre, capítulos, URLs de fallback) y las inyecta en el contenedor Blade.
- `resources/views/partials/content-single-cde.blade.php` serializa esas props en `data-media-props` dentro del `div#featured-lesson-media`. `resources/js/initFeaturedVideoPlayer.jsx` lee el JSON y monta el wrapper React.
- `resources/js/components/FeaturedLessonMedia.jsx` es el orquestador: normaliza los datos, decide si hay vídeo y/o audio y renderiza el toggle “Ver video / Escuchar audio”. Cuando sólo existe uno de los medios, el toggle desaparece.
- `FeaturedVideo.jsx` y el nuevo `FeaturedAudio.jsx` comparten utilidades:
  - `fetchMediaMetadata.js` (REST `/wp-json/espacio-sutil/v1/video-resolutions`) resuelve HLS, poster, captions y tipo de medio. El endpoint PHP `espacio-sutil-blocks/includes/api/video-resolutions.php` ahora identifica si el asset sólo contiene audio (`mediaKind='audio'`) y siempre devuelve el playlist HLS.
  - `mediaChapters.js` genera el WebVTT de capítulos a partir del subíndice ya existente; ambos players montan la pista `chapters`.
- El reproductor de audio usa `DefaultAudioLayout`, muestra la miniatura (si existe) como avatar y fuerza `viewType="audio"` para que Vidstack muestre controles específicos. El progreso de vídeo y audio se persiste con el mismo endpoint `/wp-json/espacio-sutil/v1/video-progress`, diferenciando por Stream ID.
- El toggle frontal se apoya en Tailwind y guarda el estado en memoria (no en localStorage). Cambiar de modo no reinicia el progreso del medio activo.

## 8. Subíndice jerárquico para lecciones CDE

- Se amplió el grupo de campos `Cde` (`app/Fields/Cde.php`) con un repeater `lesson_subindex_items`. Cada fila almacena `level` (1–4), `title`, `description`, `timecode` (placeholder `00:05:30`) y `anchor`. El editor ordena manualmente los ítems; la jerarquía final se deduce combinando orden + nivel.
- El composer `app/View/Composers/SingleCde.php` expone dos estructuras:
  - `lesson_subindex['items']`: árbol con hijos anidados que se usa para renderizar Blade.
  - `lesson_subindex['chapters']`: lista plana con `time` en segundos y `time_label` formateado, reutilizada por el reproductor para la pista `chapters`.
  - El helper `resolveLevel()` asegura que el valor de `level` se normalice aunque ACF devuelva texto (“Nivel 2”), mientras `normalizeTimecode()` valida tiempos `hh:mm:ss`. Se normalizan anclas con `sanitize_title`.
- Las vistas `resources/views/partials/lesson-subindex.blade.php` y `lesson-subindex-children.blade.php` renderizan listas ordenadas/viñetas con botones `data-video-seek`. El bloque se incluye después del campo `rich_excerpt` en `partials/content-single-cde.blade.php`.
- El contenedor del player añade `data-video-chapters` (JSON) que `initFeaturedVideoPlayer.jsx` parsea y pasa a `FeaturedVideo.jsx`. Este componente genera un `Blob` WebVTT para `Track kind="chapters"` y escucha clicks globales en `[data-video-seek]` para invocar `playerRef.current.currentTime`.
- Cuando no hay subíndice se omiten tanto la navegación como la pista de capítulos. El árbol (items + chapters) está listo para reutilizarse en el índice AJAX del curso sin recalcular los datos.

## 9. Cuestionario por lección (CDE)

- Los campos viven en la pestaña **Cuestionario** del grupo “Lección CDE” (`site/web/app/themes/sage/app/Fields/Cde.php`) y permiten activar/ocultar el bloque en cada lección:
  - `quiz_enabled` (true/false): controla si el cuestionario se muestra en la lección.
  - `quiz_questions` (repeater): lista de preguntas.
    - `question` (texto): enunciado.
    - `answers` (repeater): respuestas posibles.
      - `answer_text` (texto): opción.
      - `is_correct` (true/false): marca las respuestas correctas (puede haber múltiples).
  - `quiz_json_import` (textarea): importación rápida desde JSON (se procesa al guardar).
- Importación desde JSON: `App\Providers\ThemeServiceProvider::importLessonQuizFromJson()` engancha en `acf/save_post` y:
  - valida estructura y contenido (pregunta con texto, mínimo 2 respuestas, mínimo 1 correcta),
  - vuelca los datos al repeater `quiz_questions`,
  - limpia `quiz_json_import`,
  - muestra avisos en admin (ACF notice o fallback en `admin_notices`).
- Exposición a la vista: `App\View\Composers\SingleCde` construye `lesson_quiz` con preguntas normalizadas y lo marca como `enabled` solo si `quiz_enabled` y existe contenido válido.
- Renderizado: `resources/views/partials/content-single-cde.blade.php` incluye una sección `#lesson-quiz` y serializa las props iniciales en `data-quiz-props` (JSON seguro en HTML) para bootstrap del frontend.
- Frontend (mini-app):
  - `resources/js/lessons/quiz.js` monta el cuestionario con Swiper (paginación + navegación + teclado). Se deshabilita el swipe/touch (`allowTouchMove: false`) para evitar que Swiper intercepte clicks sobre checkboxes.
  - `resources/css/layouts/quiz.css` contiene los estilos del bloque (importado desde `resources/css/app.css`).
  - Barras de progreso:
    - progreso por pregunta: color sólido (usa `--quiz-question-color`, por defecto “bg-sun”).
    - resultado final: degradado “fijo” recortado por overflow (controlado por `--quiz-score-width`, por defecto 848px).
- Persistencia de resultados:
  - Endpoint REST: `App\Api\LessonQuiz` registra `POST /wp-json/cde/v1/quiz/submit` y `GET /wp-json/cde/v1/quiz/result`.
  - Se guarda en `user_meta` por usuario y lección con clave `cde_quiz_result_{postId}` e incluye `correct`, `total`, `percentage`, detalles por pregunta y timestamp.
  - Regla de acierto por pregunta: el usuario debe marcar exactamente todas las respuestas correctas (si marca una incorrecta o se deja una correcta sin marcar, la pregunta cuenta como incorrecta).

## 10. Bloques de contenidos en el índice del CDE

- La taxonomía `serie_cde` se explota ahora de forma jerárquica: los términos raíz identifican las series y los términos hijo representan cada bloque de contenidos. Ninguna entrada `cde` lleva asignado el término raíz; únicamente la lección raíz de cada bloque recibe el término hijo correspondiente.
- `app/View/Composers/TemplateCurso.php` expone una colección `series_cde_lessons` con la forma `serie → bloques`. El método `getSeriesWithBlocks()` recupera los términos raíz (`parent = 0`, `hide_empty = false`) y, para cada uno, `getBlocksForSeries()` obtiene sus términos hijo y localiza la lección raíz mediante `getBlockRootPost()`. Este último descarta candidatos cuyo ancestro ya tenga el mismo término para evitar duplicados y usa el primer resultado como fallback.
- Las series sin bloques o con términos hijo sin lección raíz se excluyen silenciosamente del acordeón, de modo que el listado frontal siempre apunta a entradas válidas.
- `resources/views/template-curso.blade.php` renderiza el acordeón con botones accesibles (`aria-expanded`, `aria-controls`, roles). Cada botón de bloque aporta `data-post-id` (ID de la lección raíz) y `data-block-term` para depuración.
- `resources/js/courses/course-index.js` controla la interacción: importa `gsap`, anima la expansión/colapso de cada panel (`fromTo`/`to` sobre height y opacity), cierra otras series abiertas y gestiona el botón activo. Al pulsar un bloque se lanza `fetch` contra `/espaciosutil/v1/indice-revelador/{post_id}?serie_name=…`, se actualiza el contenedor con el HTML devuelto y se muestran estados de carga/errores en línea.
- El árbol resultante renderizado por `partials/course-index-item` añade para cada nodo con descendientes un botón independiente (`course-index-toggle`) que controla columnas anidadas sin interferir con el enlace principal ni el icono de progreso. El JS reusa `gsap` para colapsar/expandir cada wrapper (`data-children-wrapper`) y sincroniza `aria-expanded` e iconos `+/-`.
- Los nodos sin icono de progreso utilizan un `span` placeholder (`course-index-placeholder-icon` con `aria-hidden="true"`) para mantener la alineación vertical entre iconos, enlaces y botones en todas las profundidades.
- Los estilos heredan de Tailwind; los paneles se ocultan con la clase `hidden` y un `overflow: hidden` inline para permitir la animación de altura.

## 11. Color de fondo por sección (tsparticles)

Se ha reemplazado la estrategia basada en “click de ítem de menú” por una estrategia de **sección persistente** derivada de la página actual.

### Definición de sección

- La sección se define como el **ítem de primer nivel** (ancestro top-level) del menú principal **activo por contexto**:
  - `primary_navigation` en contexto ES.
  - `cde_navigation` en contexto CDE.
- El color de sección se sigue editando en ACF sobre ítems de menú mediante `menu_item_bg_color` (`site/web/app/themes/sage/app/Fields/MenuItems.php`).
- Los ítems de primer nivel en navegación incluyen `data-section="section-{ID}"` y `data-color` (`site/web/app/themes/sage/resources/views/components/navigation.blade.php`).

### Resolución backend (fuente de verdad)

- El helper `current_navigation_section_context()` (`site/web/app/themes/sage/app/helpers.php`) resuelve la sección actual:
  - obtiene los items de la _location_ activa según `nav_context_data()`,
  - hace match de la URL actual por `path` (exacto o prefijo más largo),
  - ignora enlaces no navegables (`#`) y hosts externos,
  - asciende por `menu_item_parent` hasta el ancestro de primer nivel,
  - devuelve `key`, `color`, `menu_item_id`, `label`.
- En CDE existe fallback contextual (`fallback_navigation_section_context`) para rutas que pueden no estar en el menú (por ejemplo páginas de login/cuenta) y mantener color de contexto.
- Fallback por defecto: sección `home` con color `#000000`.
- El layout principal expone esa resolución en `<body>` con `data-section` y `data-section-color` (`site/web/app/themes/sage/resources/views/layouts/app.blade.php`).

### Comportamiento frontend

- `setBgColorAtLoadPage()` en `resources/js/nav.js` aplica siempre el color persistente desde `body[data-section-color]`.
- Al abrir un menú de primer nivel con submenú se aplica un **preview temporal** del color de esa sección.
- Si el submenú se cierra sin navegación (segundo click, click fuera, cierre de nav móvil, etc.), el fondo se restaura al color persistente de la página actual.
- Los ítems de primer nivel sin submenú (navegación directa) mantienen el cambio visual y la navegación consolida el nuevo color al entrar en la página destino.

### Integración con Barba

- En transiciones Barba se parsea `next.html`, se actualizan clases del `<body>` y también `data-section` / `data-section-color`.
- Tras sincronizar esos atributos, se reaplica el color persistente de `tsparticles`.
- Esto evita desincronizaciones cuando hay navegación AJAX sin recarga completa.

## 12. Reestructuración de navegación por contextos (ES/CDE)

Se ha consolidado una navegación por **contexto activo** para reducir la mezcla entre Espacio Sutil (ES) y Curso de Desarrollo Espiritual (CDE).

### Principios aplicados

- La **URL es la fuente de verdad** del contexto.
- Si la URL pertenece al ámbito CDE, se muestra navegación CDE.
- Si la URL pertenece al ámbito ES, se muestra navegación ES.
- El cambio de contexto envía a una URL canónica del otro contexto.

### Menús y cabecera

- El menú principal se resuelve con `nav_context_data()`:
  - `primary_navigation` para ES.
  - `cde_navigation` para CDE.
- El cambio de contexto se hace desde el propio menú principal (ítem de cruce con clase `switch` definido en WordPress).
- En contexto CDE se muestra además un enlace visual al hub CDE junto al branding (`#cde`) en desktop (`lg+`).
- En móvil se conserva una sola navegación visible (la del contexto activo, en hamburguesa).
- En páginas PMPro se añade navegación de membresía en móvil desde cabecera (`membresia_navigation`) y en desktop se mantiene en `partials/page-header`.
- La visibilidad de ítems por sesión/membresía se controla con clases de menú (`show-logged-in`, `show-logged-out`, `show-has-membership`, etc.) y reglas de ruta en `should_render_navigation_item()`.

### Contrato Barba (navegación suave)

- Se mantiene transición Barba en navegación interna de un mismo contexto.
- Se evita Barba en rutas sensibles (login, cuenta, checkout/confirmación PMP y admin).

## 13. Landing de suscripción (`template-suscripcion`)

La página de suscripción se ha rehecho como landing editorial dentro de `site/web/app/themes/sage/resources/views/template-suscripcion.blade.php`, reutilizando infraestructura nativa de WordPress, Paid Memberships Pro y componentes Blade del tema.

### Hero y cabecera

- `partials/page-header.blade.php` soporta la variante `membership-landing`.
- En esa variante:
  - el título de la página se usa como H1,
  - el excerpt se usa como subtítulo,
  - se renderizan dos CTAs fijos con `x-cta`: `Elegir plan` (`#planes`) y `Ver lección gratuita` (`/leccion-gratuita/`).
- El item de navegación principal puede mantener el label “Suscripción” aunque el título público de la página cambie.

### Tabla de precios y PMPro

- La tabla de precios ahora se alimenta de grupos nativos de PMPro:
  - `pricing-table.blade.php` obtiene los grupos con `pmpro_get_level_groups_in_order()`.
  - Los niveles del grupo se clasifican por ciclo:
    - mensual: `1 month`
    - semestral: `6 months`
    - anual: `1 year`
- La UI actual asume el modelo “3 frecuencias” para cada grupo y no implementa todavía un render genérico para ciclos arbitrarios.
- `pricing-package.blade.php` renderiza el encabezado de grupo y delega cada card de frecuencia en `pricing-plan-card.blade.php`.
- La descripción visible de cada card sale del campo nativo `description` del nivel PMPro correspondiente, no de una descripción de grupo.
- El cálculo de ahorro:
  - anual contra `12 x mensual`
  - semestral contra `6 x mensual`
  - solo muestra badge si el ahorro es real.
- Los botones de compra:
  - si el usuario ya tiene ese nivel, muestran `Suscrito` y apuntan a `pmpro_url('account')`,
  - si no, enlazan al checkout del nivel.

### Series y bloques accesibles desde la membresía

- La sección “A qué da acceso (series y lecciones)” se genera automáticamente desde la taxonomía jerárquica `serie_cde`.
- Se reutiliza `App\View\Composers\TemplateCurso`, que ahora también sirve datos a `template-suscripcion`.
- `TemplateCurso` expone `series_cde_lessons` con estructura:
  - serie raíz
  - bloques hijo
  - `lessons_count` por bloque
- El conteo de lecciones se calcula con `get_pages(child_of => $block_root_post_id)` sobre el árbol jerárquico de páginas `cde`.
- La sección de suscripción no usa AJAX ni JS para este bloque: muestra todo expandido en la carga inicial.
- Semánticamente la estructura es lista anidada:
  - `ul` de series
  - `li` por serie
  - `ul` anidada de bloques
- Las etiquetas de contador ya contemplan singular/plural:
  - `bloque` / `bloques`
  - `lección` / `lecciones`

### Listas de valor, FAQ y cierre

- La landing usa `x-list-card` para varias secciones editoriales:
  - beneficios iniciales,
  - “Qué incluye”,
  - “Cómo funciona (3 pasos)”,
  - “Para quién es”.
- Para las dos últimas se usa el icono `tabler-arrow-badge-right-filled`.
- La sección FAQ usa `faq-item.blade.php`, basada en `<details>/<summary>` sin JS adicional.
- El cierre de la landing repite los dos CTAs principales del hero para reforzar la conversión al final de la página.

### Trial gratuito personalizado en PMPro (sin add-on)

Se ha implementado un periodo de prueba gratuito propio para evitar el coste del add-on `Subscription Delays` y conservar el modelo “acceso inmediato + primer cobro diferido”.

- Archivo principal: `site/web/app/mu-plugins/espaciosutil-pmpro-trials.php`.
- La configuración vive en código, en `espaciosutil_pmpro_trial_configs()`.
- La prueba está vinculada al usuario, no a la suscripción.
- Se usa la user meta `espaciosutil_pmpro_trial_used` para recordar si una cuenta ya consumió su prueba.
- Estado actual:
  - nivel mensual PMPro `ID 11`: `7` días gratis.
  - nivel semestral PMPro `ID 12`: `7` días gratis.
  - nivel anual PMPro `ID 13`: `7` días gratis.

#### Cómo funciona técnicamente

- Se usa `pmpro_checkout_order` para modificar el pedido solo cuando PMPro ya conoce el `user_id` real del checkout, incluyendo cuentas recién creadas.
- Para un usuario elegible:
  - `initial_payment` se fuerza a `0`.
  - `trial_amount` y `trial_limit` se fuerzan a `0` para no mezclar este trial personalizado con el trial nativo de PMPro.
  - se asigna `profile_start_date = now + 7 days`.
  - el pedido queda con `subtotal = 0`, `tax = 0`, `total = 0`.
- Para un usuario que ya consumió la prueba:
  - no se aplica ninguna modificación,
  - el checkout sigue con el precio normal del plan correspondiente.
- PMPro usa `profile_start_date` para calcular el retraso del primer cobro en los gateways. En Stripe esto acaba traducido a `trial_period_days` al crear la suscripción.
- El acceso se concede desde el alta, pero el primer cargo de Stripe queda diferido hasta el final del trial.
- Tras un checkout correcto con trial aplicado, `pmpro_after_checkout` marca la user meta `espaciosutil_pmpro_trial_used = 1`.
- En desarrollo, la confirmación de pedido Stripe puede completar automáticamente pedidos en estado `token` si el webhook todavía no ha consolidado la orden, para evitar falsos negativos durante pruebas locales.

#### Copy visible al usuario

- Checkout PMPro:
  - se filtra `pmpro_level_cost_text` para mostrar un mensaje explícito solo a usuarios todavía elegibles para trial.
- Landing de suscripción:
  - `pricing-table.blade.php` detecta si un nivel tiene trial configurado y si el usuario actual sigue siendo elegible.
  - `pricing-package.blade.php` y `pricing-plan-card.blade.php` muestran una nota extra bajo el precio del plan.
  - `template-suscripcion.blade.php` refuerza el mensaje en el encabezado de la sección de planes.
- Confirmación de compra y emails:
  - se inyectan bloques dinámicos de condiciones del trial y de primer cobro real a partir de los datos del pedido.

#### Decisiones operativas

- La configuración del trial no se gestiona desde el admin de PMPro: está codificada por nivel en el mu-plugin.
- El criterio de “solo una vez” depende de la cuenta WordPress.
- El trial no usa el “primer ciclo gratis” nativo de PMPro, sino un retraso real del primer cobro.
- Si cambian los IDs de los niveles de PMPro, hay que actualizar `espaciosutil_pmpro_trial_configs()`.

### Emails transaccionales PMPro

El sistema de emails de membresía usa una estrategia mixta:

- una capa visual común controlada en código,
- plantillas clave controladas en código,
- y plantillas secundarias gestionadas desde el editor de PMPro.

#### Arquitectura

- Cabecera y pie globales:
  - `site/web/app/themes/sage/paid-memberships-pro/email/header.html`
  - `site/web/app/themes/sage/paid-memberships-pro/email/footer.html`
- Plantillas clave en código:
  - `default.html`
  - `checkout_paid.html`
  - `checkout_paid_admin.html`
  - `invoice.html`
  - `membership_recurring_trial.html`
- El resto de plantillas activas de PMPro se gestionan desde `Memberships > Settings > Email Templates`.
- Regla operativa:
  - si una plantilla se controla en código, no debe guardarse su `body` en PMPro;
  - si una plantilla se controla en el editor, no debe tener override paralelo en el tema.

#### Diseño y assets

- Los emails usan fondo oscuro en sintonía con el sitio (`#150b17`) y tipografía clara.
- La cabecera incluye branding de Espacio Sutil y del CDE.
- El footer común ya incorpora los datos legales actuales de `Libranzai, SL` y mantiene `siteemail` / `site_url` como variables dinámicas.
- Los logos del email están rasterizados en PNG y viven en `site/web/app/themes/sage/resources/images/email`.
- Los enlaces del entorno CDE usan `#b50000`.

#### Plantillas clave personalizadas

- `checkout_paid`:
  - email principal de alta para miembro,
  - incluye bienvenida, siguientes pasos, resumen del plan, condiciones del trial, prueba activada y card del pedido.
- `checkout_paid_admin`:
  - aviso interno de alta con foco operativo.
- `invoice`:
  - confirmación/recibo del pago con CTA para revisar el pedido.
- `membership_recurring_trial`:
  - plantilla propia para el primer cobro tras trial.

#### Mensajes por nivel y placeholders editoriales

- El mensaje editorial por nivel sigue usando `!!membership_level_confirmation_message!!`.
- En `setup.php` se reemplazan placeholders editoriales del proyecto como:
  - `[ENLACE_HUB]`
  - `[ENLACE_INDICE_DE_LECCIONES]`
  - `[ENLACE_LECCION_INICIO]`
  - `[ENLACE_TELEGRAM]`
  - `[ENLACE_CUENTA]`
- Para email, ese bloque se normaliza para no depender de listas HTML frágiles en clientes de correo.

#### Recordatorio recurrente y fin del trial

Este punto es deliberadamente distinto del comportamiento nativo de PMPro.

- PMPro envía por defecto el recordatorio recurrente con `7` días de antelación.
- Como el trial dura también `7` días, esa regla provocaría un recordatorio inmediato tras el alta, que no es deseable.
- Se ha sustituido el envío nativo del hook `pmpro_recurring_payment_reminder_email` por una lógica propia.
- Estado actual:
  - renovaciones normales: recordatorio a `7` días, usando la plantilla `membership_recurring` gestionada en el editor de PMPro;
  - primer cobro al terminar un trial: recordatorio a `2` días, usando la plantilla custom `membership_recurring_trial`.
- La detección de “primer cobro tras trial” se hace a nivel de suscripción y primer pedido asociado.
- Los datos del recordatorio se construyen por código para asegurar:
  - fecha exacta del primer cobro,
  - importe exacto,
  - enlace de cancelación,
  - y diferenciación entre renovación normal y fin de trial.

#### Previsualización y pruebas

- En local existe una pantalla de previsualización en `Herramientas > Emails PMPro`.
- Archivo: `site/web/app/mu-plugins/espaciosutil-pmpro-email-preview.php`.
- La preview:
  - solo se expone en desarrollo y para administradores,
  - usa pedidos reales de PMPro,
  - renderiza el HTML final sin enviar correos,
  - se muestra en documento aislado para que el CSS de `wp-admin` no contamine el email.
- El entorno local usa Mailpit para validar envíos reales.
- Para plantillas soportadas por PMPro, también puede usarse el botón nativo “Save Template and Send Email” del editor.

#### Verificación recomendada antes de producción

1. Ejecutar `wp eval-file scripts/verify-pmpro-trial.php`.
2. Hacer un checkout completo en test mode con Stripe sobre mensual, semestral y anual.
3. Confirmar en Stripe Dashboard:
   - suscripción creada,
   - `trial end` en la fecha esperada,
   - ningún cargo capturado en el alta.
4. Confirmar en WordPress/PMPro:
   - acceso concedido,
   - orden inicial a `0`,
   - membresía activa,
   - textos correctos en checkout, confirmación y emails.
5. Confirmar en Mailpit o equivalente:
   - `checkout_paid`,
   - `invoice`,
   - y, cuando corresponda, `membership_recurring_trial`.
6. Solo después repetir el mismo flujo en staging con credenciales equivalentes al entorno final.

### Responsive y layout

- Se ajustó la responsividad del banner global y de la landing de suscripción:
  - `layouts/app.blade.php`
  - `sections/header.blade.php`
  - `partials/page-header.blade.php`
  - `template-suscripcion.blade.php`
- En móvil se redujo el espacio superior del layout general y se reequilibró la cabecera CDE.
- También se evita Barba en saltos entre contextos (ES ↔ CDE).

Esto está implementado en:

- `app/helpers.php` (`should_prevent_barba_for_url`, `nav_context_from_path`, `is_barba_sensitive_path`).
- `resources/js/barba.js` (regla `prevent` y sincronización post-transición).

### Estado activo del menú con Barba

Como el header queda fuera del contenedor Barba, el estado activo no se refresca desde Blade en cada transición. Para resolverlo:

- Se implementó sincronización frontend de clases `active` y `active-ancestor`.
- Se ejecuta al cargar página y tras cada transición Barba.
- Se sincroniza también la línea de navegación desktop (`#linea`) según item activo.
- El ítem de cruce (`.switch`) no desplaza la línea de navegación al hacer click.

Archivo clave:

- `resources/js/nav.js` (`syncActiveMenuState`, `syncNavLineWithActive`).

### Estilo por contexto

- Se añadieron variables de color de enlaces de menú según `body[data-nav-context]`.
- ES usa `--color-blanco` con énfasis `--color-morado3`.
- CDE usa `--color-cde-light` con énfasis `--color-sol`.
- Se añadieron reglas específicas para submenú (`.my-child-item`) y su estado activo.
- Se añadieron estilos de enlaces de marca: hover de logotipo ES en `--color-morado3` y hover del enlace CDE en `--color-cde`.

Archivos clave:

- `resources/css/commons/navigation.css`
- `resources/views/components/navigation.blade.php`

## 14. Páginas CDE implementadas (panorámica)

Actualmente el CDE se apoya en varias páginas editoriales implementadas directamente en Blade, con una estrategia de plantillas y componentes reutilizables, no con bloques Gutenberg para cada sección.

### Páginas principales

- `template-cde-hub.blade.php`: landing/hub general del CDE. Funciona como puerta de entrada y orientación; combina explicación del proyecto, acciones principales (lección gratuita, suscripción, índice, Telegram) y resumen de qué incluye.
- `template-suscripcion.blade.php`: landing editorial de membresía. Integra la tabla de PMPro, listas de beneficios y la vista expandida de series, bloques y número de lecciones accesibles con la suscripción.
- `template-curso.blade.php`: índice del curso. Mantiene el contenido principal de la página y un navegador lateral de series y bloques con carga AJAX del índice detallado.
- `template-programa.blade.php`: página “Programa” / “El curso en profundidad”. Está construida por secciones parciales (`partials/programa/block-1` a `block-8`) y presenta el curso como un recorrido narrativo: apertura, fases, áreas, método, herramientas, enfoque práctico, fuentes y cierre.

### Componentes reutilizables del entorno CDE

- Se ha consolidado una pequeña capa de componentes Blade reutilizables para este contexto, entre ellos `x-cta`, `x-list-card`, `x-icon-card`, `x-timeline-card`, `x-source-group-card`, `x-series-blocks-list` y `x-status-card`.
- La variante tipográfica `prose-cde` se usa como base de texto largo/editorial en las páginas del curso, separada de la `.prose` general de Espacio Sutil.

### Datos compartidos entre páginas CDE

- El composer `App\View\Composers\TemplateCurso` sirve `series_cde_lessons` a `template-curso`, `template-suscripcion` y `template-programa`.
- Esa estructura alimenta tanto el índice del curso como los resúmenes editoriales de series y bloques en Suscripción y Programa, evitando duplicar lógica de taxonomías y conteos.
