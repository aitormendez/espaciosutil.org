# Prácticas: visor de escritorio y voz de Iván

Fecha: 2026-10-14. Rama: `codex/cde-practicas`. Worktree web en aitorFS: `/Users/aitor/.codex/worktrees/cde-practicas/espaciosutil.org`. Implementación candidata, sin merge, push, despliegue, instalación de skills ni recertificación del motor.

## Comportamiento implementado

Catálogo oscuro con búsqueda, filtro por serie de las lecciones y un enlace por lección, usando únicamente su título. Una práctica puede aparecer en varias series a través de sus relaciones. No hay clasificación propia ni referencia al texto fuente en la ficha. El catálogo, la ficha individual y el incrustado se apoyan en el mismo CPT y Relationship.

`partials/practice-viewer.blade.php` es el visor compartido y `resources/js/practices/viewer.js` controla cada instancia. Cada ruta de encabezados inicia su bloque; cada párrafo es una pantalla. EP-0.1 tiene 49 pantallas: 6 portadas de bloque, 15 subepígrafes y 28 párrafos, con ejecución al final. Cada padre tiene su portada; la pantalla del hijo mantiene toda la ruta visual, pero narra sólo el título nuevo. Todos los títulos llegan al motor de voz con punto final, sin añadirlo a la presentación visual. No hay progreso persistente. Un texto con listas, tablas o medios sin adaptar conserva la lectura completa, sin omitir instrucciones para forzar la conversión.

La guía se calcula en el servidor desde el HTML editorial mediante `Guide`; el navegador no divide el DOM ni inventa resúmenes. La prosa inline se sanea antes de insertarla. El formato actual permite probar la web; la API de pantallas y los IDs estables para la app aún deben diseñarse. La lectura completa funciona sin JavaScript.

«Activar audio» reproduce la pantalla actual y cada nueva pantalla hasta desactivarlo. Cambiar de pantalla corta el fragmento previo; terminar un audio no avanza. Activar otra práctica o un medio de la lección detiene la narración anterior. «Texto completo» la pausa y regresar a la guía mantiene la pantalla. El índice sigue visible en lectura y sus botones enfocan y desplazan a los encabezados reales del texto; en pantalla completa se puede mostrar u ocultar y el texto tiene scroll interno. No se usan las voces del navegador.

Pantalla completa oculta el índice inicialmente y permite mostrarlo u ocultarlo sin reiniciar audio ni posición. Escape y el botón de salida restauran el contexto. Los botones usan los iconos Tabler acordados. El diseño y la validación se limitan al escritorio.

## Instalación de voz comprobada

El sistema existente está en aitorFS, dentro de `Contenidos/pruebas-voz/2026-09-23-entrenamiento-qwen3-ivan`, con índice en `Contenidos/pruebas-voz/README.md`. Se ha leído y utilizado como dependencia; no se ha modificado su código, checkpoint, estado ni documentación.

- Motor: MLX 0.32.2 y MLX Audio 0.5.5, Qwen3-TTS 12Hz 1.7B Base bf16; inferencia local en el Mac.
- Python comprobado: el entorno `Contenidos/tmp/qwen3-tts-training/venv/bin/python` funciona con Python 3.12.9.
- Adaptador: `checkpoints/epoch-3/adapters.safetensors`, SHA-256 `02079a760772e103260f30331b6817efd2c189f4a727fca89eae041ad5ebbfb5`, intensidad 0,25; referencia de voz F063.
- El entrenamiento está completado. Los documentos de selección aún dicen «candidato para escucha», aunque el historial recoge aprobación auditiva del candidato; no se han cambiado esos archivos operativos para corregir su estado.
- No es un servicio HTTP instalado en WordPress ni una voz de Ollama. Se generan archivos previamente en el Mac y la web los sirve a los miembros.

El nuevo `scripts/generate-practice-audio.py` importa únicamente las funciones de carga del paquete existente; no ejecuta sus comandos de entrenamiento/evaluación ni sus escrituras de estado. Usa modo offline y desactiva bytecode. Exporta MP3, WAV y metadatos con texto, referencia, semilla, duración y SHA-256 en la ruta de salida explícita. Si encuentra archivos previos, comprueba sus metadatos y huellas antes de reutilizarlos. La revisión auditiva se marca como pendiente para cada nueva práctica.

## Preparación y protección de los audios

Desde el host, en `site`, obtener un plan sin escrituras con permisos editoriales:

```bash
wp eval-file scripts/practice-audio-plan.php ID_PRACTICA --user=EDITOR > /ruta/plan.json
```

En el Mac con el entorno MLX existente:

```bash
/ruta/Contenidos/tmp/qwen3-tts-training/venv/bin/python scripts/generate-practice-audio.py /ruta/plan.json --voice-bundle /ruta/Contenidos/pruebas-voz/2026-09-23-entrenamiento-qwen3-ivan --output /ruta/site/private/cde-practicas-audio
```

Los audios se guardan en `site/private/cde-practicas-audio/POST_ID/HUELLA/PANTALLA.mp3`, fuera de `web/`. La huella depende de todas las pantallas; un cambio de contenido requiere preparar una nueva versión. El visor sólo habilita audio cuando existen todos sus fragmentos. Antes de instalar en otro entorno, generar el plan con los IDs de ese entorno y trasladar los archivos revisados a su almacenamiento privado; no reutilizar ciegamente IDs locales.

`Audio::serve()` exige membresía activa o permiso editorial y valida publicación, contraseña, índice y huella actual. Responde con audio privado `no-store`; las versiones antiguas e índices inexistentes devuelven 404. La ruta es `/?cde_practica_audio=1&practice=…&screen=…&revision=…`, sin pasar por `admin_init`, donde la VM aislada intentaba consultar actualizaciones externas. Excluir expresamente este parámetro y las rutas de prácticas de las cachés externas antes de instalar. El endpoint sirve fragmentos completos, sin implementación de rangos HTTP ni búsqueda dentro de un fragmento.

## Prueba local aislada

Se ha creado una copia del tema y mu-plugin en aitorFS `/Volumes/D/Desarrollo/Sites/espaciosutil.org/site/tmp/cde-practicas-preview/`, accesible desde la VM mediante el tema temporal `sage-practicas-preview`. Un cargador local ignorado por Git sólo funciona en `WP_ENV=development` y `espaciosutil.test`, y sólo para la cookie optativa `cde_practicas_preview=1` o la variable CLI `CDE_PRACTICAS_PREVIEW=1`. No cambia la opción global del tema. Reutiliza las ubicaciones de menús de Sage sin escribirlas.

Abrir `/practicas/?cde_practicas_preview=1` activa la vista durante un día; `/?cde_practicas_preview=0` la desactiva para ese navegador. Continúa siendo necesaria la membresía. La ficha de prueba es `/practicas/sentir-el-suelo-prueba-local/` (post 2998), y la lección sintética es `/lecciones-del-cde/practicas-leccion-prueba-local/` (post 2999). Esta relación sirve para QA y no declara resuelto el inventario de la lección real 0.4.3.2. No se ha escrito en NocoDB.

Se han añadido únicamente una cuenta y una membresía gratuitas de QA, la práctica, la lección sintética y las reglas locales de enlaces. El estado necesario para la retirada está en `site/tmp/cde-practicas-preview/state.json` (privado; no versionar ni compartir). Para retirar el piloto, eliminar sólo estos objetos identificados tras verificar sus IDs, el cargador local, el enlace de tema y las carpetas de prueba/audio; regenerar las reglas con el tema normal. No restaurar indiscriminadamente la base de datos ni las reglas antiguas si otras tareas han trabajado después.

En esta sesión `espaciosutil.test` no resolvía en el Mac, aunque Lima estaba sirviendo en `127.0.0.1:80`. El Chrome de QA usa una regla de resolución exclusiva de su proceso; no se ha modificado `/etc/hosts`. Para usar otro navegador hace falta resolver el dominio local. Matomo tampoco resuelve en esta VM y muestra un error ajeno al visor. El login local mostró además un error de consulta de traducciones externas, aunque permitió iniciar sesión; no se ha desactivado el aislamiento de red.

## Validación y límites

- Build Vite y comprobación de directivas Blade; avisos preexistentes de Vidstack y tamaño de chunks.
- 32 comprobaciones PHP de sólo lectura: jerarquías de tres niveles, párrafos íntegros, fallback sin omisión, saneamiento, huellas, membresía, relaciones y búsqueda/filtro por serie.
- Chrome a 1440 px: navegación, índice, lectura completa, reproducción real de MP3, cambios de pista, ausencia de avance automático, pantalla completa y protección HTTP. Evidencia en `docs/qa/practicas/desktop-20261014/`.
- 43 MP3 generados: 482,4 s de audio en 240,43 s de inferencia; ningún sample saturado. No se ha hecho transcripción automática de estos nuevos fragmentos ni validación perceptiva; requieren escucha antes de publicar.

El motor, sus contratos/certificados y las skills de Paperclip no cambian. La integración de las ramas, el inventario remoto, la revisión editorial y auditiva, la distribución de audio privado y la instalación coordinada siguen siendo pasos posteriores. Esta prueba local no acredita despliegue ni aprobación de móvil.

## Archivos de esta entrega

- `docs/diseno/practicas/README.md`.
- `docs/practicas-cde-implementacion.md`.
- `docs/practicas-cde-visor-y-voz.md`.
- `docs/practicas-cde.md`.
- `docs/qa/practicas/README.md`.
- `docs/qa/practicas/desktop-20261014/README.md`.
- `docs/qa/practicas/desktop-20261014/practicas-catalogo.png`.
- `docs/qa/practicas/desktop-20261014/practicas-fullscreen.png`.
- `docs/qa/practicas/desktop-20261014/practicas-incrustado.png`.
- `docs/qa/practicas/desktop-20261014/practicas-individual.png`.
- `scripts/generate-practice-audio.py`.
- `site/scripts/practice-audio-plan.php`.
- `site/tests/cde-practicas-browser.js`.
- `site/tests/cde-practicas-viewer.php`.
- `site/web/app/mu-plugins/cde-practicas/audio.php`.
- `site/web/app/mu-plugins/cde-practicas/catalog.php`.
- `site/web/app/mu-plugins/cde-practicas/guide.php`.
- `site/web/app/mu-plugins/espaciosutil-cde-practicas.php`.
- `site/web/app/themes/sage/resources/css/app.css`.
- `site/web/app/themes/sage/resources/css/layouts/practices.css`.
- `site/web/app/themes/sage/resources/js/app.js`.
- `site/web/app/themes/sage/resources/js/practices/viewer.js`.
- `site/web/app/themes/sage/resources/js/transitionScripts.js`.
- `site/web/app/themes/sage/resources/views/archive-practica.blade.php`.
- `site/web/app/themes/sage/resources/views/partials/content-single-practica.blade.php`.
- `site/web/app/themes/sage/resources/views/partials/lesson-practicas.blade.php`.
- `site/web/app/themes/sage/resources/views/partials/practice-icon.blade.php`.
- `site/web/app/themes/sage/resources/views/partials/practice-viewer.blade.php`.

## Ajustes posteriores del 2026-10-14

La primera prueba con 43 pantallas se sustituye por una versión con 49, conforme a la indicación de Aitor de incluir las portadas de bloque. El texto enviado a TTS se separa del título visual: sólo se narra el último epígrafe de la ruta, terminado en punto. Esto invalida correctamente los audios anteriores mediante una nueva huella; se conservan los archivos anteriores en privado como evidencia, pero el visor sirve la nueva versión.

Cada pantalla ya se sintetizaba de forma independiente; no se segmentaba una grabación larga. Se inspeccionó la decodificación de MLX: antepone los códigos de la referencia F063 y elimina su tramo al final. En tres muestras, el recorte proporcional difería del límite exacto en cero o una muestra (0,042 ms), de modo que no se atribuye el problema de décimas de segundo a un simple redondeo. El inicio audible es compatible con arrastre del contexto acústico de esa referencia.

La corrección prepara una copia de F063 con 480 ms de silencio final (seis frames del códec), sin alterar sus muestras originales. Durante la síntesis se captura la decodificación completa y se retira la referencia por el número exacto de frames de 1.920 muestras, validando dimensiones y rechazando recortes aproximados. No se elimina un intervalo fijo del comienzo de la voz generada ni se recortan fonemas por un umbral de volumen. El modelo y la biblioteca operativos permanecen intactos; este tratamiento reside en el generador del worktree.

Las primeras tres muestras nuevas empiezan con RMS cero en los primeros 30 ms; dos muestras anteriores tenían RMS 0,016179 y 0,001922. La transcripción local con Whisper conserva el texto de los dos títulos y el párrafo de prueba; representa la numeración de manera desigual, por lo que no se declara coincidencia literal total ni aprobación perceptiva. Las muestras nuevas deben escucharse para confirmar subjetivamente la desaparición de los sonidos señalados. La transcripción usa dependencias temporales en `/tmp/cde-practicas-asr-deps`, sin modificar el entorno de entrenamiento.

Pruebas adicionales: cuatro regresiones Python del límite exacto y de conservación de F063; 35 comprobaciones PHP, incluyendo portadas, puntuación y anclas únicas por visor; comprobación en Chrome del índice en ambas pestañas y en pantalla completa. Las capturas nuevas se conservan en `docs/qa/practicas/desktop-20261014/`. La evidencia de 43 pantallas del apartado previo corresponde a la primera iteración y se mantiene como historial.

Resultado de la regeneración: 49 fragmentos y 437,6 segundos de audio en 225,89 segundos de inferencia; cero muestras saturadas. Todos los límites de referencia fueron exactamente 270.720 muestras (141 frames). El RMS máximo de los primeros 30 ms fue 0,0000261, aproximadamente −91,7 dBFS. Esto acredita el arranque casi silencioso, sin sustituir una revisión auditiva completa. Las 35 comprobaciones PHP y las cuatro pruebas Python pasaron.

## Numeración explícita para la voz

Los encabezados se envían al motor con su numeración escrita en palabras: «1.1. En este corpus, canalizar es permitir que la energía pase» se narra a partir de «Uno punto uno. En este corpus, canalizar es permitir que la energía pase.». Cada componente se separa con «punto» y la numeración termina con una pausa de frase. Se conserva el punto final del epígrafe y sólo se narra el último título de la ruta, sin repetir los padres. La portada «1. …» recibe «Uno. …».

La transformación reside en `Guide`, antes de calcular la huella y exportar el plan de audio; por tanto, el nuevo texto invalida correctamente la versión de voz anterior. Los títulos visibles, el índice y la lectura completa mantienen cifras. Los párrafos no se transforman. Se admiten componentes de hasta tres cifras como cardinales; los ceros iniciales y componentes más largos se verbalizan dígito a dígito, evitando perder información o desbordar enteros. No hay dependencia nueva de extensiones PHP ni cambios en el motor Qwen3, su checkpoint o las skills.

La previsualización local usa una revisión nueva. Se regeneran los 21 encabezados y se reutilizan los 28 fragmentos de párrafo, comprobando igualdad del contenido, metadatos de síntesis y SHA-256. Las versiones anteriores permanecen en almacenamiento privado. No se realizan integración en `Contenidos`, recertificación ni instalación en Paperclip.

Validación: 47 comprobaciones PHP, recorrido del visor en Chrome y reconocimiento correcto de la numeración en los 21 encabezados por Whisper local. Las diferencias de puntuación y la normalización «consciencia/conciencia» no se consideran una comprobación de fidelidad textual literal. [Resultados de numeración](qa/practicas/desktop-20261014/numeracion-v3-resultados.json).

## Práctica en su lección original y ventana libre (iteración retirada)

Este apartado conserva el historial de una prueba retirada por petición de Aitor. La entrada 3000 está en la papelera y el diseño lateral se ha eliminado. La ubicación vigente se describe en el siguiente apartado.

El 2026-10-14 se incorporó a la base local la lección `cde:08:00:0.4.3.2`, «La canalización como permiso y el ejercicio de sentir» (post 3000), desde el TXT original `08 - El Círculo Carmesí/00 - Presentación/0.4. - Geoffrey Hoppe y la naturaleza de la canalizacion/0.4.3.2/0.4.3.2. - La canalizacion como permiso y el ejercicio de sentir.txt`, relativo a AITOR CDE. No existía una entrada correspondiente en la copia local. Esta incorporación proporciona el texto real para revisar el diseño; no equivale a una publicación editorial procesada. No se inventan medios ni se altera el TXT. La huella de fuente es `b1c5fe8b127901bfd0da361ed236a12161139397ca2ef16a8c2a60fa0d8b8be3`. Las 60 líneas del cuerpo se conservan literalmente y en orden en 14 encabezados y 17 párrafos; la jerarquía previa al título y el índice duplicado se representan mediante el contexto y el subíndice.

La entrada cuelga del contenedor existente «El Círculo Carmesí. Introducción» (2961), con la taxonomía existente de Presentación (80). La jerarquía editorial intermedia 0.4/0.4.3 no se ha creado. Se asigna EP-0.1 (2998) mediante ACF Relationship y se retira su relación de la lección ficticia 2999, conservando esa entrada para no borrar trabajo. La copia local está protegida con los niveles de membresía existentes 11–14. Los campos `_cde_local_practice_context` y `_cde_local_preview_source_sha256` identifican la preparación local y su fuente. Antes de cualquier sincronización editorial posterior deberá reconciliarse esta copia por clave, no duplicarse ni exportarse automáticamente.

La referencia de diseño consultada es el archivo Penpot `Espacio-Sutil-CDE`, página `Práctica - wireframe`, board `03 · Práctica en lección / Escritorio · 1440` (`3ceec200-b26f-803d-8008-c9f61a3ec1b6`). No se modificó Penpot. Las lecciones con prácticas visibles incorporan navegación lateral «En esta lección», columna compartida de texto y práctica, fondo oscuro, enlace a la ficha y ancla directa al incrustado. El resto de lecciones conserva su estructura. Se reutilizan los parciales y el motor de guía existentes; no hay cambios de audio, skills ni motor editorial.

La ventana anterior imponía un viewport emulado de 1440 × 1050. La nueva sesión de revisión usa `viewport: null`, conserva la autenticación de pruebas y resuelve `espaciosutil.test` normalmente. El visor depende de su columna y crece con el texto; la ventana puede redimensionarse. A 1440, 1280 y 1024 px, el visor midió respectivamente 992, 913 y 657 px, sin desbordamiento horizontal. Esto valida el escritorio, no una nueva propuesta móvil.

Abrir [la lección local y su práctica](http://espaciosutil.test/lecciones-del-cde/el-circulo-carmesi-introduccion/la-canalizacion-como-permiso-y-el-ejercicio-de-sentir/?cde_practicas_preview=1#lesson-practices-3000). La URL activa la copia optativa del tema sólo en ese navegador y sigue requiriendo sesión con membresía. El tema global, `Contenidos`, Paperclip, sus skills y certificados no cambian.

Trazabilidad y reversión local: `site/tmp/cde-practicas-preview/real-lesson-plan.json` conserva el plan y HTML; `real-lesson-apply.json` conserva el ID creado y la relación anterior de 2999. Para retirar esta preparación, restaurar esa relación sólo si sigue vacía, poner 3000 en borrador sólo si conserva la marca y huella esperadas y desactivar la cookie con `/?cde_practicas_preview=0`. No borrar audios ni datos del usuario. Estos archivos de ejecución y las credenciales de navegador quedan ignorados por Git.

Validación: 47 comprobaciones PHP sobre la práctica 2998, la lección 3000 y el miembro de QA 29; compilación Vite; navegación de escritorio en Chrome con dimensiones nativas, anclas al texto y práctica, ruta 1/1.1, índice del texto completo y pantalla completa. [Resultados](qa/practicas/desktop-20261014/leccion-original-resultados.json) y [captura](qa/practicas/desktop-20261014/practicas-leccion-original.png).

La respuesta anónima oculta el cuerpo docente y el visor y muestra el acceso a sesión. El SEO Framework conserva su descripción pública automática, como en el resto del sitio; no se declara ausencia de todo extracto en los metadatos. La consulta inversa de EP-0.1 devuelve únicamente 3000. Los scripts de preparación y aplicación se conservan junto al plan en el directorio local ignorado para auditoría.

## Revisión temporal en una lección completa

Por indicación de Aitor, EP-0.1 (2998) se asocia temporalmente a «Qué es el Círculo Carmesí» (2967, clave `cde:08:00:0.1`), que ya contiene texto, vídeo, audio, subíndice y cuestionario. Esta asociación permite revisar el diseño y no constituye una atribución editorial de la práctica a esa lección. Se retira la relación de 3000 y se envía esa entrada a la papelera, tras verificar su marca local y que su contenido coincide con el recibo de creación. La consulta inversa devuelve ahora únicamente 2967.

Se restaura la plantilla habitual de la lección, eliminando la envoltura, la navegación lateral y el fondo global añadidos en la iteración anterior. El visor conserva su panel oscuro y los parciales compartidos. Sólo sus controles y rellenos se adaptan al ancho disponible; el texto, los medios, el subíndice y el cuestionario de la lección anfitriona se mantienen.

[Abrir la lección completa con la práctica](http://espaciosutil.test/lecciones-del-cde/el-circulo-carmesi-introduccion/que-es-el-circulo-carmesi/?cde_practicas_preview=1#lesson-practices-2967). La sesión de Chrome usa una ventana nativa redimensionable. La previsualización sigue siendo optativa, local y protegida por membresía. Se conserva la protección de los niveles 11–13 y se añade el nivel local de QA 14 a esta lección para permitir la revisión con el miembro de pruebas existente; no se altera ninguna suscripción.

La aplicación local conserva copia previa completa de ambos posts, sus metadatos y las restricciones de acceso en `site/tmp/cde-practicas-preview/transfer-to-complete-lesson.json`; el script se conserva junto a ella como `transfer-to-complete-lesson.php`. Para revertir la preparación, restaurar los campos de relación y niveles desde ese recibo, comprobando antes que no hayan cambiado por otra tarea. La entrada 3000 permanece recuperable en la papelera; no restaurarla automáticamente. El post 2967 y todos sus metadatos previos, salvo los dos campos de la nueva relación ACF, se comprobaron sin modificaciones.

Validación: compilación Vite de la previsualización; Chrome con vídeo y audio configurados, subíndice, cuestionario, guía, índice de lectura y tres anchuras de ventana (1440, 1280 y 1024 px) sin desbordamiento. La respuesta anónima sigue sin permiso ni visor. [Captura actual](qa/practicas/desktop-20261014/practicas-leccion-completa.png). La prueba PHP histórica de 47 aserciones se ejecutó sobre la fixture anterior y no se presenta como nueva validación de 2967. No cambia el motor de guía, el audio, las skills, Paperclip ni los certificados; todo el código permanece en el worktree y su rama, sin integración ni despliegue.
