# Tema Sage 11 para Espacio Sutil

Este es el tema personalizado para el sitio web de **Espacio Sutil**, basado en [Sage 11](https://roots.io/sage/), con las siguientes tecnologías integradas:

- 🎨 [Tailwind CSS 4.1](https://tailwindcss.com) para estilos modernos y utilitarios
- ⚡️ [Vite](https://vitejs.dev) para desarrollo front-end con recarga instantánea
- 🧠 [Laravel Blade](https://laravel.com/docs/10.x/blade) como sistema de plantillas
- 🌱 [Acorn](https://github.com/roots/acorn) para acceso a herramientas de Laravel en WordPress
- 🧩 [Acorn Post Types](https://github.com/roots/acorn-post-types) para registrar post types y taxonomías desde configuración

## Requisitos

- Node.js >= 18
- PHP >= 8.1
- Composer
- npm (⚠️ no se utiliza Yarn en este proyecto)

> **Importante**: Este proyecto **no utiliza Yarn** debido a problemas de compatibilidad con scripts del ecosistema WordPress. Se ha eliminado `.yarn/`, `.pnp.*` y `yarn.lock`.

## Instalación

1. Clona el repositorio del proyecto (o navega al directorio del tema dentro del stack Bedrock).

2. Ejecuta la instalación de dependencias:

   ```bash
   npm install
   composer install
   ```

3. Compila los assets para producción:

   ```bash
   npm run build
   ```

   O para desarrollo:

   ```bash
   npm run dev
   ```

## Estructura del tema

- `resources/` – Archivos Blade, CSS, JS, fuentes y plantillas
- `public/` – Salida compilada de Vite
- `app/` – Configuración Acorn, Service Providers y lógica del tema
- `composer.json` – Dependencias PHP
- `package.json` – Scripts y dependencias JS
- `vite.config.js` – Configuración de Vite
- `theme.json` – Configuración de estilos y bloques para el editor

## Funcionalidades Clave

## Curso de Desarrollo Espiritual

El tema implementa un Custom Post Type (CPT) `cde` para gestionar el contenido del "Curso de Desarrollo Espiritual".

- **Índice Jerárquico Automatizado:** Las páginas del curso utilizan un índice jerárquico que se genera automáticamente, facilitando la navegación y estructuración del contenido.
- **Miga de pan jerárquica:** Cada lección `cde` muestra su cadena de padres dentro del header de la entrada. El composer `app/View/Composers/Post.php` reúne el árbol y añade un enlace raíz fijo al curso, mientras que la vista `resources/views/partials/post-header.blade.php` lo renderiza en formato apilado en móviles (una línea por nivel con prefijo `>`) y horizontal a partir de `md`, asegurando accesibilidad sin sobrecargar la interfaz.
- **Extracto Enriquecido:** Cada entrada del curso puede tener un extracto enriquecido (WYSIWYG) gestionado a través de un campo de [Advanced Custom Fields (ACF)](https://www.advancedcustomfields.com/). Este extracto es visible para todos los usuarios.
- **Contenido Restringido:** El contenido principal de las entradas del curso está dividido en dos partes: un extracto público y el contenido completo, que es accesible únicamente para usuarios con una membresía activa.

#### Subíndice de la lección

Cada lección dispone de un subíndice editorial que se muestra tras el extracto y que alimenta los capítulos del video.

1. En la edición de la lección abre el grupo **Subíndice de la lección**.
2. Puedes crear apartados manualmente (botón “Añadir apartado”) o importar un JSON en el campo **Importar subíndice desde JSON**.
3. Si importas, el JSON debe ser un array de objetos con `title` (string), `level` (1–4), y opcionalmente `timecode` (`hh:mm:ss`) y `anchor` (slug sin `#`):

   ```json
   [
     { "title": "Introducción", "level": 1, "timecode": "00:01:12", "anchor": "introduccion" },
     { "title": "Referencias", "level": 2, "timecode": "00:03:45", "anchor": "referencias" },
     { "title": "Conclusión", "level": 1 }
   ]
   ```

4. Al guardar la lección, el JSON se valida, rellena el repeater y el campo se limpia. Los elementos quedan disponibles para retoques manuales.
5. El campo `Nivel` controla la profundidad (1 = raíz, 2–4 = hijos). La jerarquía se reconstruye combinando orden + nivel.
6. Las marcas de tiempo generan chips interactivos y una pista `chapters` en el player para usuarios con acceso; los visitantes no logueados ven una versión estática del índice.

### Video Destacado con Bunny.net

Cada lección del curso (`cde`) puede incluir un video destacado, alojado en Bunny.net, que se muestra al principio del contenido.

#### Campos en el Editor

El video se configura desde el editor mediante tres campos personalizados de ACF:

- `featured_video_id`: ID del video en la Video Library de Bunny.net.
- `featured_video_library`: ID de la Video Library asociada.
- `featured_video_lang`: Código de idioma predeterminado para los subtítulos (`es`, `en`, `fr`, etc).

Estos campos están definidos en `app/Fields/FeaturedVideo.php` mediante AcfComposer.

#### Subida y Formato de Video

Los videos deben subirse a la Video Library de Bunny.net indicada, e incluir archivos de subtítulos en formato `.vtt`, con nombres como:

- `captions/es.vtt`
- `captions/en.vtt`
- `captions/fr.vtt`

Bunny.net detecta automáticamente los subtítulos cargados y los expone mediante su API. El player Vidstack carga dinámicamente estos subtítulos y permite seleccionar idioma desde el interfaz.

#### Renderizado del Player

El bloque personalizado `espacio-sutil-blocks` renderiza el player Vidstack en la plantilla `content-single-cde.blade.php` si se detecta un `featured_video_id`. Los subtítulos se cargan automáticamente desde la carpeta `captions/` del CDN y se muestran en pantalla con soporte multilenguaje.

#### Conmutador de anchura del video destacado

El reproductor de la lección `cde` incluye un conmutador integrado en los controles que alterna entre:

- Ancho completo (predeterminado).
- Columna centrada con `max-w-4xl` (estilo alineado al contenido de la lección).

Detalles de comportamiento y accesibilidad:

- El botón aparece únicamente en pantallas de escritorio (`min-width: 768px`). En dispositivos móviles, el reproductor permanece a ancho completo.
- La alternancia es instantánea y no recarga el video ni pierde el progreso.
- El botón incluye `aria-label` y tooltip para accesibilidad y consistencia con el UI de Vidstack.

Ubicación de la implementación:

- Lógica y botón: `resources/js/components/FeaturedVideo.jsx`
- Contenedor del player: `resources/views/partials/content-single-cde.blade.php`

Nota: Por ahora, la preferencia no se persiste. Si se requiere, puede añadirse con `localStorage`.

### Audio destacado y modo “Escuchar”

Además del vídeo, cada lección puede ofrecer una pista de audio alojada en la misma Video Library de Bunny.net. Cuando hay audio y vídeo disponibles, el frontend muestra un selector para elegir entre “Ver video” y “Escuchar audio”; si solo se proporciona audio, el modo se activa automáticamente.

#### Campos en el editor

Dentro del grupo **Video destacado** se añadieron tres campos nuevos:

- `featured_audio_id`: Stream ID del audio en Bunny.net (obligatorio para mostrar el reproductor de audio).
- `featured_audio_library_id`: ID de la Video Library. Déjalo con el valor por defecto salvo que el audio viva en otra librería.
- `featured_audio_name`: Etiqueta opcional que se muestra en el UI. Si se deja vacío, se usa el título de la lección.

#### Flujo editorial

1. Sube el archivo de audio (mp3/wav/etc.) a la **Video Library** de Bunny; aunque no tenga pista de vídeo, Bunny Stream genera automáticamente un HLS de audio.
2. (Opcional) Agrupa los audios en una colección propia de la librería para diferenciarlos de los vídeos.
3. Copia el `Video ID` del asset y pégalo en `featured_audio_id`. Si usas otra librería, especifica también `featured_audio_library_id`.
4. Guarda la lección. El subíndice de capítulos (repeater ACF) se reutiliza tanto para vídeo como para audio, por lo que los saltos funcionan en ambos modos.

#### Comportamiento en frontend

- El progreso de escucha y visionado se guarda de forma independiente usando el endpoint `/wp-json/espacio-sutil/v1/video-progress` (el Stream ID actúa como clave).
- El reproductor de audio usa Vidstack con el layout `DefaultAudioLayout` e incorpora la miniatura de Bunny como portada circular encima de los controles.
- Los capítulos se inyectan como pista `chapters`, y los botones del subíndice hacen “seek” en el medio actualmente activo.
- Si no se define `featured_audio_id`, no se muestra el toggle ni el reproductor de audio.

#### Sistema de Marcación de Lecciones

Este tema incluye un sistema para que los usuarios registrados puedan marcar lecciones del curso como "vistos".

#### Funcionalidad

- Cada usuario puede marcar o desmarcar cualquier lección del CPT `cde` como completada.
- Esta información se guarda como un array de IDs en la meta `user_meta` de WordPress (`cde_completed_lessons`).
- El estado se conserva entre sesiones y es totalmente individual para cada usuario.

#### Implementación Técnica

- **Backend:** Se utiliza un endpoint personalizado de la REST API (`/wp-json/cde/v1/complete/`) que permite registrar o eliminar una lección como completada mediante una petición `POST`.
- **Composer:** El View Composer del partial `content-single-cde` calcula si la lección actual está completada y expone la variable booleana `$is_completed` para las vistas.
- **Frontend:** Un botón con iconos permite marcar o desmarcar la lección directamente desde la vista de la lección. Los iconos se gestionan con [Blade Icons](https://blade-ui-kit.com/blade-icons) y cambian dinámicamente mediante JavaScript.
- **Persistencia Dinámica:** El botón actualiza su estado mediante una petición AJAX sin necesidad de recargar la página, modificando las clases de forma coherente con el diseño de Tailwind CSS.

#### Integración en el Índice del Curso

- El índice de lecciones también muestra el estado de cada lección:
  - Icono de "visto" (`<x-coolicon-show />`) si la lección ya fue completada.
  - Icono de "no visto" (`<x-coolicon-hide />`) si la lección no ha sido completada.
- Estos iconos se renderizan directamente en Blade al generar el índice, respetando el estado guardado para cada usuario.

#### Lecciones inactivas

- El grupo de campos `Cde` incluye el toggle `active_lesson`, activado por defecto.
- Al desactivarlo, la lección sigue apareciendo en el índice, pero se muestra sin enlace y con un estilo atenuado para indicar que aún no está disponible.
- Esta información se propaga desde el endpoint `espaciosutil/v1/indice-revelador` hacia la vista `partials/course-index-item`, que decide si renderizar el elemento como enlace o como texto estático.

#### Resumen

Este sistema permite que los estudiantes lleven un control visual y funcional de su progreso en el curso, sin necesidad de plugins adicionales, integrándose perfectamente con la arquitectura del tema.

#### Organización por series

- La taxonomía `serie_cde` es jerárquica: los términos raíz representan las series y sus términos hijo representan los bloques de contenido de cada serie.
- Cada bloque debe tener una lección raíz `cde` asociada únicamente al término hijo correspondiente; las lecciones descendientes permanecen sin término y se anidan mediante el campo padre (`post_parent`).
- El término raíz solo sirve para generar el botón de la serie dentro del índice, por lo que no debe asignarse a ninguna entrada.
- Para crear un bloque nuevo: crea el término hijo bajo la serie, asigna ese término a la lección raíz del bloque (puedes nombrarla siguiendo el patrón `Serie › Bloque`) y organiza el resto de lecciones como hijas de esa entrada raíz.
- La plantilla `template-curso` renderiza un acordeón: el botón de serie despliega sus bloques y, al elegir un bloque, se carga su índice jerárquico vía AJAX; el orden de los bloques se controla con `term_order` y el de las lecciones con `menu_order`.
- Las antiguas entradas de `revelador` se mantienen para el resto de contenidos del sitio, pero ya no se utilizan para el CPT `cde`.

#### Control de acceso por membresía (Paid Memberships Pro)

- Cada lección del `cde` respeta las restricciones definidas en el metabox **«Requerir membresía»** de Paid Memberships Pro.
- Al editar una lección, selecciona los niveles que deben tener acceso; si no se marca ninguno, la lección queda abierta para todos los usuarios.
- Los usuarios con vistas previas de PMPro (selector de la admin bar) también se rigen por ese metabox, por lo que puedes comprobar el comportamiento con «Ver con acceso de membresía» o «Ver sin acceso».

### Membresías y Navegación (Paid Memberships Pro)

El tema integra el sistema de membresías [Paid Memberships Pro (PMP)](https://www.paidmembershipspro.com/) para gestionar el acceso a contenido restringido.

- **Navegación Condicional:** La navegación específica de PMP (tanto para escritorio como para móvil) se muestra de forma condicional, apareciendo únicamente en las páginas relevantes de membresía (ej. Cuenta, Facturación, Cancelación, Pedidos, Perfil, Pago).
- La navegación de las secciones privadas se gestiona mediante la librería [Log1x/Navi](https://github.com/Log1x/Navi), que permite administrar los menús desde el editor de WordPress.
- Los menús de membresía se renderizan con condicionales según el estado de la cuenta (sesión iniciada, membresía activa) mediante un único componente de Blade, compartido por las versiones de escritorio y móvil, con clases unificadas de Tailwind CSS.

### Página de Suscripciones Personalizada

El tema incluye una página personalizada de suscripciones que reemplaza el listado estándar de niveles de Paid Memberships Pro. Esta página utiliza un sistema de tarjetas configurables desde ACF para mostrar cada **serie de contenidos** disponible en formato de suscripción.

#### Características de la implementación

- **Agrupación por serie**: Cada tarjeta representa una serie de contenidos con múltiples planes disponibles (mensual, semestral y anual).
- **Gestión centralizada desde ACF**: Se utiliza un campo `Repeater` en la página de opciones (`Opciones`) con los siguientes campos:
  - `monthly_level_id`: ID del nivel de suscripción mensual
  - `semiannual_level_id`: ID del nivel semestral (opcional)
  - `yearly_level_id`: ID del nivel anual
  - `display_name`: Nombre de la serie que se muestra en la tarjeta
  - `short_description`: Descripción breve de la serie
  - `image`: Imagen ilustrativa de la serie
  - `order`: Campo numérico para ordenar la visualización

#### Funcionalidad en el Frontend

- Cada tarjeta muestra los precios mensuales, semestrales y anuales si están disponibles.
- Se calcula automáticamente el ahorro (%) de los planes más largos respecto al mensual.
- Los botones de suscripción cambian de estado si el usuario ya está suscrito:
  - Muestran el texto **"Suscrito"**
  - Enlazan a la página de cuenta (`pmpro_url('account')`)
- Los datos de estado de suscripción se gestionan mediante la función `pmpro_hasMembershipLevel()` y se pasan como props a los componentes Blade.
- Se utilizan atributos `data-subscribed` y `data-state` en los botones para permitir estilos condicionales en CSS/JS.

#### Accesibilidad y compatibilidad

- Todos los botones de suscripción tienen atributos `aria-label` dinámicos.
- La maquetación de las tarjetas está basada en Tailwind CSS y responde al diseño móvil-escritorio con `flex-wrap` y breakpoints (`sm:`).

## Desarrollo y Configuración

- Este tema es parte del stack Roots (Trellis + Bedrock + Sage).
- El despliegue se realizará mediante GitHub Actions (configuración pendiente).
- Se recomienda mantener el uso de `npm` para garantizar compatibilidad con el editor de bloques y herramientas nativas de WordPress.
- **Gestión de Campos ACF con ACF Composer:** Las definiciones de los campos de Advanced Custom Fields (ACF) se gestionan directamente en el código del tema, ubicadas en `app/Fields/`, utilizando la librería [Log1x/AcfComposer](https://github.com/Log1x/AcfComposer). Esto permite un control de versiones y una gestión más robusta de los campos personalizados.

* **Tipografía enriquecida (`prose`) desde cero:** Implementada en `resources/css/commons/typography.css` con tamaños relativos (`em`) para escalado proporcional. Se incluyen variantes `prose-xl` y `prose-2xl`, utilizables con prefijos responsivos (`md:prose-xl`, `lg:prose-2xl`).

  - **Exclusión selectiva con `.not-prose` (marcador, no utilidad):** No existe una clase Tailwind `not-prose` con estilos. En su lugar, `.not-prose` actúa como **marcador** para desactivar el alcance de `.prose` mediante selectores negativos en el propio stylesheet, p. ej.:

    ```css
    .prose p:not(:where(.not-prose, .not-prose *)) {
      /* … */
    }
    ```

    De este modo, cualquier subárbol marcado con `class="not-prose"` queda fuera de las reglas de `.prose`, incluso estando anidado dentro de un contenedor `.prose`.

  - **Ejemplo de uso** (contenido con tipografía enriquecida y bloque de plugin sin estilizar):

    ```blade
    <article class="prose md:prose-xl lg:prose-2xl">
      <h1>Título</h1>
      <p>Texto del cuerpo…</p>

      <div id="pmpro_levels" class="not-prose">
        {!! do_shortcode('[pmpro_levels]') !!}
      </div>
    </article>
    ```

  - **Motivación:** Evitar dependencia del plugin `@tailwindcss/typography` (que requiere `tailwind.config.{js,cjs}`) y controlar la especificidad: el sistema usa selectores de baja especificidad y escalado relativo para mantener consistencia visual y facilitar overrides.

## Bloque de video con Bunny.net

El tema utiliza un plugin personalizado llamado `espacio-sutil-blocks` para añadir bloques reutilizables, entre ellos un bloque de video integrado con Bunny.net.

- El bloque permite insertar un video de una Video Library de Bunny.net mediante su `libraryId` y `videoId`.
- Se utiliza la API de Bunny.net para obtener dinámicamente las resoluciones disponibles y la miniatura (`thumbnailUrl`) mediante un endpoint personalizado de la REST API de WordPress.
- Los datos de autenticación (clave API y pull zone) se configuran en el archivo `.env`, gestionado desde los `vault.yml` de Trellis:
  - `BUNNY_KEY`
  - `BUNNY_PULL_ZONE`
- El plugin se compila automáticamente durante el despliegue, gracias a un hook definido en `trellis/deploy-hooks/build-before.yml`.

> Este bloque ha sido migrado y adaptado desde un proyecto previo, y validado tanto en el editor como en el frontend.

## Sección de Lecciones Relacionadas

El tema incluye una sección para mostrar lecciones relacionadas al final de cada entrada del CPT `cde`.

### Funcionalidad

- Muestra un mosaico de "tarjetas" con la imagen de póster del video destacado y el título de otras lecciones.
- Cada tarjeta enlaza directamente a la lección relacionada correspondiente.
- La selección de lecciones relacionadas es manual, permitiendo un control editorial completo.

### Implementación Técnica

- **Campo de Relación ACF:** La relación se gestiona a través de un campo `relationship` de ACF llamado `cde_related_lessons`. Este campo está definido en `app/Fields/RelatedLessons.php` mediante `AcfComposer` y está restringido para mostrar únicamente posts del tipo `cde`.
- **View Composer:** El composer `app/View/Composers/SingleCde.php` se encarga de:
  1.  Obtener los objetos de post de las lecciones relacionadas a través del campo ACF.
  2.  Iterar sobre cada lección relacionada para obtener su título, enlace permanente y el `featured_video_id`.
  3.  Construir la URL del póster (`thumbnail.jpg`) utilizando el `featured_video_id` y la variable de entorno `BUNNY_PULL_ZONE`.
  4.  Pasar un array con todos estos datos a la vista.
- **Plantilla Blade:** El archivo `resources/views/partials/videos-realacionados-cde.blade.php` recibe los datos del composer y renderiza el mosaico de tarjetas. Si una lección relacionada no tiene un póster, se muestra un recuadro gris como placeholder.

## Créditos

Desarrollado y mantenido por Aitor Méndez como parte del proyecto de modernización del sitio **Espacio Sutil**, utilizando el stack Roots (Trellis, Bedrock, Sage 11) y tecnologías web contemporáneas.
