# Prácticas CDE: referencia de implementación

Actualizado: 2026-10-16. Estado: implementación web de escritorio y móvil validada y desplegada junto a presentaciones en staging desde `codex/cde-staging-practicas-presentaciones`. Véase [versión, pruebas y reversión](../infraestructura/staging-practicas-presentaciones-2026-10-16.md). La rama original `codex/cde-practicas` se conserva; main y producción siguen pendientes de integración; la adaptación web móvil se ha desplegado por autorización del usuario el 2026-10-16 y queda pendiente de su revisión visual. Esta es la referencia del comportamiento vigente; los informes anteriores conservan el historial y las evidencias de cada iteración.

Para integrar las prácticas en el flujo versionado de lecciones, leer el [traspaso de automatización](../relevo/practicas-automatizacion-lecciones.md): fuentes, conversión de outline, contrato de pantallas, TTS de Iván, recorte exacto, límites del importador y trabajo pendiente. Ese documento no activa la integración.

## Alcance y decisiones

Una práctica es un contenido reutilizable del curso: instrucciones, meditaciones u otros procedimientos que el alumno puede consultar desde el catálogo, desde su ficha individual o dentro de las lecciones relacionadas. El nombre público y técnico acordado es «Práctica» / `practica`, aunque una fuente conserve «Ejercicio práctico» y su código editorial.

- Catálogo en `/practicas/`, con búsqueda y filtro por serie de las lecciones asociadas. La serie no clasifica la práctica: una misma práctica puede aparecer en varias series. El filtro contempla los términos de la lección y los heredados de sus lecciones padre, incluyendo los bloques descendientes de la serie, igual que el índice del curso.
- Ficha en `/practicas/<slug>/` e incrustado en la lección, con el mismo parcial Blade y controlador de visor.
- Una práctica admite cero, una o varias lecciones relacionadas. Cada lección se muestra como un único enlace con su título, sin controles duplicados.
- El contexto público son las lecciones relacionadas. No se añade una referencia al texto fuente ni una taxonomía de clasificación de prácticas en esta fase.
- La práctica exige cualquier membresía PMPro activa; existe acceso editorial de previsualización. Las relaciones expresan contexto, no niveles de acceso. La integración conserva las restricciones existentes de la lección anfitriona: no migra automáticamente sus reglas de membresía.
- Web primero, con adaptación responsive para móvil autorizada el 2026-10-16. Las aplicaciones nativas Android/iOS siguen pendientes; una prueba en un navegador redimensionado no equivale a QA en un dispositivo físico.

## Datos y edición en WordPress

| Dato | Ubicación y contrato |
| --- | --- |
| Título, instrucciones, extracto | Campos nativos del CPT `practica`; admite revisiones. El extracto sirve al catálogo. |
| `clave_practica` | Identidad estable para reconciliar el inventario y WordPress; no usar el título como clave. |
| `codigo_practica` | Código editorial, por ejemplo `EP-0.1`; no presupone unicidad entre proyectos. |
| `cde_practicas` | ACF **Relationship** en cada lección `cde`, devuelve IDs de prácticas. Clave estable `field_cde_practicas`. |
| Lecciones relacionadas | Consulta inversa de ese Relationship; no se mantiene una segunda lista manual en la práctica. |

Para editar una relación, abrir la lección y la pestaña **Prácticas** del grupo **Lección CDE** (`group_cde_fields`), después de «Lecciones relacionadas». El selector no debe reaparecer como grupo independiente. El grupo «Práctica CDE» sí pertenece a la ficha del CPT y conserva sus campos propios.

`Catalog::fields()` registra la pestaña y la relación en `acf/init` con prioridad 110, después de ACF Composer. Conservar nombre y clave del Relationship evita migraciones de metadatos y mantiene compatible el importador. Aunque el grupo anfitrión admite REST, `acf/rest/get_fields` excluye este campo; no abrirlo accidentalmente al reorganizar el editor.

El incrustado excluye prácticas no publicadas o con contraseña. La consulta inversa excluye lecciones no publicadas, inactivas o con contraseña y las ordena por orden de lección y título. El catálogo ordena las prácticas por título. Las relaciones temporales usadas para diseño no constituyen procedencia editorial confirmada.

## Inventario y procedencia

El diseño propone una tabla NocoDB `cde_practicas`, relacionada con `cde_lecciones`, y claves estables `clave_practica` / `clave_leccion`. La referencia WordPress de una práctica identifica su propio post; no duplica los IDs o URLs de las lecciones. La API remota, el esquema vivo, su migración y el write-back no están conectados por esta implementación.

El preparador del worktree de Contenidos trabaja con un export normalizado. El importador web usa `plan` y `apply`: crea borradores y añade asociaciones confirmadas; no publica, no retira asociaciones por ausencia en un export parcial y no sobrescribe textos existentes distintos. Detecta identidades ambiguas y cambios posteriores al plan. Los comandos, límites y coordinación están en [implementación e inventario](../practicas-cde-implementacion.md).

La fuente de EP-0.1 procede editorialmente de `0.4.3.2 — La canalización como permiso y el ejercicio de sentir`. La correspondencia técnica exacta en el inventario debe verificarse antes de importar esa relación; no deducirla de `EP-0.1` ni de una asociación local de QA. Los TXT se conservan una sola vez en la carpeta de la pieza de procedencia, con el inventario del proyecto en su raíz. Véanse [decisiones y fuentes](../practicas-cde.md).

## Texto, pantallas e índice

La adaptación editorial convierte los niveles numerados en encabezados. Cada idea principal sin numerar forma un párrafo; sus explicaciones subordinadas se incorporan con punto y seguido, conservando las ideas hermanas en párrafos separados. No inventar resúmenes ni omitir instrucciones para hacer caber el contenido en pantallas.

`Guide` construye la guía desde el HTML editorial en el servidor. Cada encabezado y cada párrafo adaptado tiene su pantalla. Cada bloque padre dispone de portada propia; un subepígrafe muestra la ruta completa de padres e hijo. La narración dice únicamente el encabezado nuevo para evitar repeticiones. Los textos que contienen estructuras no adaptadas, como listas, tablas o medios, conservan la lectura completa en lugar de forzar una guía incompleta.

EP-0.1 contiene 49 pantallas: 6 portadas, 15 subepígrafes y 28 párrafos. Son cifras de esta práctica, no un límite del visor. El contexto, ritmo y avisos preceden a la ejecución paso a paso, situada al final como bloque 6. El TXT fuente conserva su orden original.

El avance es manual. «Texto completo» conserva todo el contenido y funciona también sin JavaScript. Existe un único índice por visor: en guía selecciona pantallas; en lectura desplaza y enfoca los encabezados del texto. Cambiar de modo conserva la pantalla de guía. No existe todavía progreso persistente ni un contrato de pantallas con IDs estables para la app.

## Voz de Iván

«Activar audio» reproduce la narración de la pantalla actual y de cada pantalla a la que se navega mientras esté activo. No avanza al terminar. Cambiar de pantalla interrumpe el fragmento previo; desactivar audio, pasar a lectura o activar otro medio detiene la reproducción correspondiente. No se utilizan voces del navegador ni síntesis en cada petición web.

Todos los encabezados se entregan al motor con punto final aunque la pantalla no lo muestre. La numeración se verbaliza antes de calcular la huella: `1.1. Título` se convierte en «Uno punto uno. Título.». La ruta de padres sigue visible, pero no se vuelve a narrar en la pantalla del hijo.

Se utiliza la instalación local Qwen3-TTS / MLX y el adaptador de Iván existente. Se genera un archivo por pantalla, con metadatos y huellas; no se corta una pista larga en tiempos aproximados. La corrección del límite de referencia usa silencio de 480 ms, alineación a frames del codec y recorte exacto para evitar restos del audio de referencia al principio. El detalle del modelo, parámetros, comandos y pruebas está en [visor y voz](../practicas-cde-visor-y-voz.md).

Los archivos viven en `site/private/cde-practicas-audio/POST_ID/HUELLA/`, fuera de `web/`. El endpoint `?cde_practica_audio=1&practice=…&screen=…&revision=…` comprueba acceso, publicación, contraseña y versión; sirve respuestas privadas sin caché. El audio sólo se habilita cuando están disponibles todos los fragmentos de la revisión actual. Un cambio de contenido invalida la huella anterior; preparar y revisar una nueva versión antes de habilitarla. Los IDs de posts son específicos de cada entorno.

## Composición visual de escritorio

La sección incrustada ocupa todo el ancho disponible. El título, el contenido del visor y los enlaces se centran al mismo ancho que los demás componentes de la lección: `--container-4xl`, con fallback de 56 rem y márgenes de seguridad. El índice queda fuera de esa columna, pegado al borde izquierdo; «Texto completo» utiliza toda la columna central, sin un segundo índice lateral.

`ResizeObserver` mide el espacio real del margen: el índice se muestra automáticamente si caben sus 256 px más 24 px de separación y se oculta si no cabe. El botón permite abrirlo y cerrarlo en ambos modos. La elección manual se conserva al cambiar de pestaña y vuelve al estado automático al cruzar el umbral de espacio por redimensionado. El observador se desconecta al desmontar la instancia.

Cuando no cabe, la apertura manual muestra un panel superpuesto sin estrechar ni desplazar la columna. Tiene cierre propio y admite Escape; seleccionar un destino lo cierra y lleva el foco al contenido. El índice usa posicionamiento sticky y desplazamiento propio. En pantalla completa empieza oculto y su botón permite recuperarlo; al salir se restablece el contexto de la página.

Los controles usan Tabler: `arrow-badge-left` y `arrow-badge-right` filled para avanzar y retroceder, audio/audio tachado y controles de pantalla completa e índice.

### Fondo y separadores de bloques opcionales

El fondo común es `morado5` (`#150b17`) al 90 % de opacidad. Dentro de `lesson-study-content`, la práctica, el visor y el índice lateral dejan pasar ese fondo para no sumar capas oscuras. El panel de índice superpuesto usa el mismo tono sólido para asegurar legibilidad.

La composición de estudio reúne resumen, práctica, presentación opcional y cuestionario. Cada bloque presente que tiene otro bloque delante aporta un único separador superior de 1 px `#353535`, a todo el ancho del contenedor, con 40 px de separación interior. La regla vive en `lesson-separators.css`, importada después de `practices.css`; utiliza `container-type: inline-size` y `100cqw` sin ensanchar la columna de lectura.

Los parciales sin contenido no deben renderizar una sección vacía ni un separador suelto. El CSS excluye además elementos `:empty` y `[hidden]`; un contenedor con espacios o marcado vacío no es una alternativa a la condición Blade. Se suprimen los bordes propios de la práctica dentro del grupo y se conserva su borde inferior sólo si no viene presentación ni cuestionario después. Así, al faltar práctica o presentación, no se apilan filetes. La presentación usa `includeIf`: esta rama admite el parcial, pero no incorpora la implementación de presentaciones desarrollada por otro hilo.

## Adaptación web móvil

Por debajo de 1024 px, el catálogo usa una columna y el visor comparte la misma distribución compacta en la ficha y dentro de la lección. Las pestañas ocupan una primera fila; índice, audio y pantalla completa una segunda. Los botones mantienen al menos 44 px de altura, iconos Tabler de 20 px y nombres accesibles completos, aunque el texto visible sea «Índice», «Audio» o sólo el icono de pantalla completa. El estado de audio conserva el contraste activo y `aria-pressed`.

La guía utiliza encabezados de 27–28 px, contexto de 16 px y párrafos de 20 px; la lectura continua usa 18 px. El contador se abrevia visualmente a `1 / 49`. En pantalla completa se respetan la altura dinámica del viewport y las áreas seguras, con controles y navegación separados del área central desplazable. Cada cambio de pantalla devuelve el texto al principio. En horizontal de poca altura, los controles se reúnen en una sola fila.

El índice empieza oculto en pantallas compactas, tanto en la ficha como en el incrustado. Se abre encima del contenido sin reservar una columna lateral y tiene cierre propio; al abrirlo recibe el foco el botón de cierre. Elegir un bloque cierra el panel y lleva al contenido, también en «Texto completo». Al regresar a escritorio se conserva la lógica de espacio disponible del incrustado. La pantalla completa sigue dependiendo de la API del navegador: el control se oculta cuando no está disponible.

La adaptación no modifica el contenido, la segmentación ni los archivos de voz, los permisos, las relaciones ACF o el motor editorial. Su prueba reutilizable es `site/tests/cde-practicas-mobile-browser.js`; preparar `output/playwright/practicas-mobile/` antes de ejecutarla con una sesión autorizada. Comprueba seis tamaños, recorre todas las pantallas a 320 px y en horizontal, verifica navegación, índice, lectura y audio, y guarda capturas. Las comprobaciones de dispositivo físico y la aprobación visual del usuario siguen pendientes.

## Mapa de código y pruebas

Las rutas siguientes son relativas a la raíz del repositorio. En las filas del tema, el prefijo es `site/web/app/themes/sage/`.

| Responsabilidad | Archivo |
| --- | --- |
| Carga del módulo | `site/web/app/mu-plugins/espaciosutil-cde-practicas.php` |
| CPT, ACF, acceso y relaciones | `site/web/app/mu-plugins/cde-practicas/catalog.php` |
| Conversión a pantallas y texto narrado | `site/web/app/mu-plugins/cde-practicas/guide.php` |
| Entrega de audio privado | `site/web/app/mu-plugins/cde-practicas/audio.php` |
| Plan y aplicación de importaciones | `site/web/app/mu-plugins/cde-practicas/import.php` |
| Catálogo | `resources/views/archive-practica.blade.php` |
| Ficha e incrustado | `resources/views/partials/content-single-practica.blade.php`, `lesson-practicas.blade.php` |
| Visor e iconos compartidos | `resources/views/partials/practice-viewer.blade.php`, `practice-icon.blade.php` |
| Composición de la lección | `resources/views/partials/content-single-cde.blade.php` |
| Interacción del visor | `resources/js/practices/viewer.js` |
| Estilos | `resources/css/layouts/practices.css`, `lesson-separators.css` |
| Plan y generación de voz | `site/scripts/practice-audio-plan.php`, `scripts/generate-practice-audio.py` |
| Pruebas PHP | `site/tests/cde-practicas-integration.php`, `site/tests/cde-practicas-viewer.php`, `site/tests/cde-practicas-series.php` |
| Pruebas de generación | `scripts/test_generate_practice_audio.py` |
| Recorridos de navegador | `site/tests/cde-practicas-browser.js`, `cde-practicas-adjustments-browser.js`, `cde-practicas-lesson-browser.js`, `cde-practicas-mobile-browser.js` |
| Combinaciones de secciones | `site/tests/lesson-separators-browser.js` |

## Validación registrada y cómo continuar

Los resultados corresponden a las ejecuciones documentadas, no a una nueva ejecución de todas las pruebas con cada edición de este documento.

- [QA general](../qa/practicas/README.md): importación, acceso y límites de los entornos de prueba.
- [QA del 14 de octubre](../qa/practicas/desktop-20261014/README.md): guía, voz y numeración; 47 comprobaciones PHP sobre la fixture de aquel momento y cuatro pruebas Python de referencia/audio. Las pruebas sobre la lección 3000 son históricas, pues esa entrada se retiró.
- [QA del 15 de octubre](../qa/practicas/desktop-20261015/README.md): columna de 896 px, anchuras útiles aproximadas de 1761/1405/1121 px, índice adaptable, lectura, foco y ausencia de desbordamiento. Pantalla completa comprobada de forma independiente en la ficha individual con Playwright.
- [QA móvil del 16 de octubre](../qa/practicas/mobile-20261016/README.md): seis tamaños, recorrido completo de 49 pantallas en vertical y horizontal, audio, índice, lectura, ficha, catálogo y regresión de escritorio; release `20261016083020`.
- Separadores: build Vite, compilación y sintaxis Blade, inspección de la lección 2389 y ejecución de `lesson-separators-browser.js` con las cuatro combinaciones de práctica/presentación y un caso de elementos vacíos/ocultos. La prueba construye fixtures sólo en el navegador, sin escribir en WordPress.

Al modificar el visor, repetir el recorrido afectado con una cuenta que tenga acceso a la lección y ventana redimensionable: guía/lectura, índice con y sin margen, cierre y foco, pantalla completa, narración y coexistencia con medios. Al modificar la composición, ejecutar las combinaciones de secciones. No presentar el recorrido reutilizable de lección como ejecutado sobre un post al que la cuenta de QA no tiene acceso ni restaurar relaciones antiguas para hacerlo pasar.

## Aislamiento y previsualización local

El desarrollo se conserva en `/Users/aitor/.codex/worktrees/cde-practicas/espaciosutil.org`, rama `codex/cde-practicas`. El checkout web compartido está en `/Volumes/D/Desarrollo/Sites/espaciosutil.org`. Una rama diferente dentro de la misma carpeta no ofrece aislamiento de archivos.

La previsualización optativa usa una copia ignorada en `site/tmp/cde-practicas-preview/` del checkout compartido, un cargador local y el tema temporal `sage-practicas-preview`. Se activa en `espaciosutil.test` mediante `?cde_practicas_preview=1` y se desactiva con `/?cde_practicas_preview=0`; requiere sesión autorizada. Sólo funciona en desarrollo y no cambia el tema global. Para WP-CLI se usa `CDE_PRACTICAS_PREVIEW=1`, desde el host y el directorio `site` del entorno conectado a la VM.

La copia de previsualización contiene también trabajo de presentaciones de otro hilo. Antes de sincronizar un cambio, comparar los archivos y trasladar sólo lo atribuible a prácticas; no reemplazar el tema completo ni sus bundles por los de esta rama. Compilar la copia con `npm run build -- --base=/app/themes/sage-practicas-preview/public/build/` desde su carpeta `theme/`. Mantener privados e ignorados los recibos, `state.json`, credenciales y sesiones de navegador.

La última revisión del incrustado usó la práctica local 2998 en «Qué es El libro de Urantia» (2389), relación que ya había asignado el usuario. [Abrir la previsualización de esa lección](http://espaciosutil.test/lecciones-del-cde/urantia/que-es-el-libro-de-urantia/?cde_practicas_preview=1#lesson-practices-2389). Es una ubicación de diseño, no su procedencia definitiva. La relación temporal con 2967 quedó vacía, la entrada incompleta 3000 está en la papelera y la fixture 2999 quedó sin relación; no restaurarlas automáticamente. Verificar el estado vivo antes de una nueva preparación o retirada.

## Integración en staging y límites pendientes

La integración web en staging fue autorizada y completada el 2026-10-16. No promoverla a producción ni modificar el checkout operativo de Contenidos, su certificado, catálogo de skills o configuración de Paperclip sin coordinación posterior. Los documentos `docs/cde-*.md` y otros archivos del flujo forman parte de las huellas certificadas: incluso una edición sin commit puede invalidar una lección en curso. Recertificar el checkout no migra automáticamente las lecciones vinculadas a una versión anterior.

Esta actualización documental no cambia motor, voz, skills ni configuración operativa. La preparación de inventario y su skill candidata están descritas en [implementación e integración diferida](../practicas-cde-implementacion.md); no se consideran instaladas por existir en una rama.

Antes de promover a otro entorno: revisar divergencias, reconciliar inventario y asociaciones y validar versiones, permisos y cachés del destino. Staging ya dispone del código conjunto, audio privado persistente, reglas de enlaces y navegación; no reutilizar sus IDs automáticamente en producción. Mantener separadas integración web, certificación del motor e instalación de skills. Quedan pendientes la conexión remota NocoDB, la API para apps y la aprobación visual de la adaptación móvil.


## Puente de automatización de lecciones

El mu-plugin `espaciosutil-cde-practices-automation.php` incorpora el puerto WP-CLI de Contenidos 3.5.0: preview canónico sin crear fichas, importación por clave, audio privado verificado, relación ACF aditiva y sincronización con la publicación real de la lección. No expone un endpoint HTTP de escritura. La práctica nueva permanece en borrador hasta que el anfitrión sea público; una práctica compartida ya pública no se retira por enlazarla a un borrador. La revisión de contenido y escucha completa se acredita en el motor antes de importar.

Esta copia es preparación local para despliegue, no acredita instalación en staging ni producción. Depende del módulo Prácticas CDE y de Guide/Audio de esta rama. La comprobación autenticada del 16 de octubre encontró que producción aún no tiene el CPT `practica` ni el módulo. No copiar IDs ni relaciones de diseño. El contrato portable y las regresiones están en Contenidos, `docs/cde-practices-automation.md`, release 3.5.0. Al desplegar hay que comprobar que el SHA del puente coincide con el certificado del motor y completar un piloto autenticado antes de declarar la integración operativa.
