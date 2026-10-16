# Traspaso: integrar las prácticas en la automatización de lecciones CDE

Fecha: 2026-10-16. Este documento reúne las decisiones de la conversación, el código disponible y las comprobaciones realizadas para que otra conversación implemente la integración. **No implementa ni activa esa integración.** La tarea futura es que `cde-lesson-full-flow` detecte las prácticas entregadas en la carpeta de una lección, prepare su contenido y narración y cree o reutilice su ficha y relación al procesar esa lección.

Los hechos comprobados se distinguen de las propuestas para la automatización. Las rutas absolutas se refieren al Mac de Aitor (`aitorFS`); no presuponen la misma estructura en otro equipo. Los enlaces relativos apuntan a este repositorio web. Referencia general: [Prácticas CDE](../context/practicas-cde.md).

## 1. Punto de partida y dónde continuar

| Pieza | Ubicación y estado observado |
| --- | --- |
| Web conjunta | `/Users/aitor/.codex/worktrees/cde-staging-practicas-presentaciones/espaciosutil.org`, rama `codex/cde-staging-practicas-presentaciones`. Código móvil `7668a02`; documentación y QA `5046505` antes de este traspaso. |
| Web original de prácticas | `/Users/aitor/.codex/worktrees/cde-practicas/espaciosutil.org`, rama `codex/cde-practicas`. Es una referencia histórica; continuar desde la rama conjunta para conservar presentaciones y correcciones posteriores. |
| Candidata de Contenidos | `/Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos-worktrees/cde-practicas`, rama `codex/cde-practicas`, HEAD observado `4eeef12`. Incluye preparador, esquema, pruebas, skill candidata y adaptación de EP-0.1. |
| Skill que deberá evolucionar | `/Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos/.agents/skills/cde-lesson-full-flow/SKILL.md`. Leída, no modificada en este traspaso. |
| Staging web validado | Release `20261016083020`, código `7668a0250f6f122b44615756a86603ff9b302db2`. Véanse [despliegue](../infraestructura/staging-practicas-presentaciones-2026-10-16.md) y [QA móvil](../qa/practicas/mobile-20261016/README.md). No se ha vuelto a consultar el servidor al redactar este documento. |

El checkout operativo de Contenidos contiene cambios ajenos, incluidos la skill y el motor. No trasladarlos, revertirlos ni tomarlos como una versión instalada. Su skill actual ya exige declarar las capacidades nuevas con `--require-capability` y respetar runtimes inmutables. El archivo de configuración leído declara `version: 3.2.2` y no incluye una capacidad de prácticas, mientras que la skill contiene instrucciones de evoluciones posteriores: **estos archivos de desarrollo no acreditan qué runtime usa una lección real**. Resolver la instalación y el manifiesto efectivos al empezar la implementación.

Huella de la skill leída para este traspaso: `295568b0eff720935bcc7d841b88a29a5fa97a6be45d56f430204eea78807de1`. Es una fotografía de lectura, no un certificado ni una autorización para operar.

## 2. Objetivo funcional y decisiones que deben conservarse

- Nombre público y CPT: **Práctica**, `practica`. Las fuentes pueden llamarse «Ejercicios prácticos» y conservar códigos `EP-…`; no cambiar por ello el nombre del CPT.
- Una ficha reutilizable por práctica. Puede relacionarse con cero, una o varias lecciones, incluso de distintas series. No crear una copia de la práctica por cada aparición.
- `cde_practicas` es un campo ACF **Relationship**, clave `field_cde_practicas`, dentro de la pestaña «Prácticas» de «Lección CDE» (`group_cde_fields`). No crear otro grupo de campos para la relación.
- Datos de la ficha: título, cuerpo, extracto, `clave_practica` y `codigo_practica`. El contexto público son las lecciones relacionadas mediante consulta inversa del Relationship. Cada lección aparece una sola vez como enlace con su título.
- Sin clasificación propia de prácticas por ahora. El filtro por serie del catálogo se deriva de las lecciones y de su ascendencia, no asigna una serie exclusiva a la práctica.
- No se exige una referencia al texto fuente en la ficha pública. La procedencia, ruta y huellas sí deben conservarse como evidencia técnica.
- Acceso a las prácticas con cualquier membresía PMPro activa; acceso editorial de previsualización. No inventar niveles específicos. La lección anfitriona conserva sus reglas existentes.
- Catálogo, ficha y práctica incrustada ya existen. Ficha e incrustado comparten Blade, guía y controlador. La web tiene adaptación móvil; las apps nativas y su API quedan pendientes.

## 3. Detección de fuentes y asociación con la lección

### Acuerdo de entrega de Iván

El TXT de cada ejercicio se guarda una sola vez, en la carpeta de la pieza de la que procede directamente: tema, profundización o reprofundización. Cada proyecto conserva en su raíz un TXT con la relación completa y las ubicaciones. Las antiguas carpetas generales «Ejercicios prácticos» y el listado conjunto de la raíz AITOR CDE se retiraron; no usarlos como fuente vigente ni reconstruirlos automáticamente.

La petición actual es detectar la práctica en esa carpeta durante el procesamiento de la lección. La detección debe partir de **la carpeta fuente ya resuelta para la identidad exacta de la lección**. El código `EP-0.1` no identifica la lección `0.1`. No usar semejanza de títulos ni una búsqueda recursiva indiscriminada que atribuya al padre prácticas de sus lecciones hijas.

Para el futuro adaptador: registrar ausencia comprobada como «sin prácticas»; distinguirla de carpeta inaccesible, lectura fallida o inventario contradictorio. Admitir varias prácticas en una carpeta. Excluir derivados y `- PROCESADO` de la búsqueda de originales, y registrar los criterios de detección. Una correspondencia inequívoca entre carpeta de la lección, entrega y fuente debe poder resolverse automáticamente; las ambigüedades conservan su diagnóstico, sin inventar la asociación ni pedir confirmaciones rutinarias para casos ya resueltos.

La ubicación de origen permite asociar la primera lección. Otras lecciones pueden mencionar la misma práctica: reutilizar su identidad y añadir relaciones confirmadas, sin copiar el TXT. Una búsqueda textual puede sugerir esas apariciones, pero no las convierte por sí sola en relaciones verificadas.

### Caso real EP-0.1

- Práctica: «Sentir el suelo: una capa de energía cada vez».
- Procedencia editorial confirmada por Iván y el inventario del proyecto: `0.4.3.2 — La canalización como permiso y el ejercicio de sentir`, El Círculo Carmesí.
- Fuente comprobada de nuevo al redactar este traspaso: `/Users/aitor/Library/CloudStorage/Dropbox/AITOR CDE/08 - El Círculo Carmesí/00 - Presentación/0.4. - Geoffrey Hoppe y la naturaleza de la canalizacion/0.4.3.2/EP-0.1. - Sentir el suelo una capa de energia cada vez.txt`.
- SHA-256 del TXT: `ccf46e9ab58a816b5673bc0edfbc19e8fa6ae66b872b7d654ab9c6764cb053d8`.
- Markdown adaptado: `/Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos-worktrees/cde-practicas/docs/practicas/ep-0.1-sentir-el-suelo.md`; SHA-256 `b7547c0abfbec4f3d14fca057a6ca2e9737d784dfaba90f6b8a12d63125f7763`.
- En staging la práctica 2998 está relacionada para pruebas con «Qué es El libro de Urantia», lección 2389. **Esa asociación es de diseño y no acredita procedencia editorial.** No exportar esos IDs o esa relación a producción ni convertirlos en ejemplo de reconciliación del inventario.

Una huella acredita identidad de bytes, no aprobación editorial ni identidad de una fila NocoDB. Resolver en el destino su `clave_leccion` y su ID real; no reutilizar IDs locales por coincidencia.

## 4. Conversión de outline a texto legible

Esta es una transformación editorial conservadora: eliminar la presentación en viñetas sin resumir, añadir instrucciones ni alterar el sentido.

1. Separar los metadatos de cabecera —proyecto/bloque, código y título de la práctica— del cuerpo. El título de la ficha no es una pantalla de instrucciones duplicada.
2. Reconocer y excluir del cuerpo el índice inicial que repite los encabezados; el índice del visor se genera desde el contenido real. No eliminar una lista por el mero hecho de parecer un índice si su función es ambigua.
3. Convertir los niveles numerados del cuerpo en encabezados. En Markdown: bloque `##`, subapartado `###`, siguiente nivel `####`; en el HTML consumido por la guía: `h2`, `h3`, `h4`, hasta `h6`.
4. Cada idea principal sin numerar bajo un epígrafe se convierte en un párrafo.
5. Las ideas subordinadas sin numerar de esa idea se incorporan al mismo párrafo, en su orden, como frases separadas por **punto y seguido**. Profundizar la anidación no debe crear automáticamente más pantallas.
6. Una nueva idea principal inicia otro párrafo. No unir todos los párrafos de un apartado ni separar una pantalla por cada oración.
7. Preservar literalmente las palabras, citas, tildes y signos que ya sean correctos. No duplicar puntos ni producir secuencias como `?.`; si un fragmento sin puntuación no permite una unión segura, señalar la necesidad de adaptación editorial.
8. Los títulos visibles quedan sin punto final de frase. No quitar puntos internos ni los separadores de su numeración. La puntuación del TTS se prepara por separado.

Ejemplo de la fuente:

```text
- En estas enseñanzas, comunicarse con lo que no se ve empieza por sentir, antes que por pensar.
  - Tobías lo dejó dicho al hablar de sus comienzos: quien espera oír palabras lo tendrá difícil, porque lo que llega «viene de sentir».
  - Lo que se recibe es un sentimiento, y la mente le pone después los conceptos y las palabras.
```

Resultado: un párrafo y, por tanto, una pantalla:

> En estas enseñanzas, comunicarse con lo que no se ve empieza por sentir, antes que por pensar. Tobías lo dejó dicho al hablar de sus comienzos: quien espera oír palabras lo tendrá difícil, porque lo que llega «viene de sentir». Lo que se recibe es un sentimiento, y la mente le pone después los conceptos y las palabras.

La fuente real usa sangría y marcadores `•`, `⚬`, `▪` y `-`; los guiones repetidos de los ejemplos del chat son una representación ilustrativa, no el único formato que debe aceptar el parser. La implementación futura debe probar la sintaxis real y rechazar estructuras ambiguas, no asumir que todo número es un epígrafe o que toda lista es prosa subordinada. Una lista de pasos, alternativas o advertencias puede necesitar conservar su función.

Al redactar este traspaso se comprobó que las **71 líneas de prosa** de la fuente de EP-0.1 están presentes literalmente en el Markdown adaptado, que contiene 6 bloques, 15 subepígrafes y 28 párrafos. Esta comprobación de presencia no sustituye por sí sola una prueba de orden y ausencia de duplicados para un convertidor general.

### Orden acordado: contexto antes de ejecución

Iván aceptó el 2026-10-12 reorganizar EP-0.1 para dejar la práctica ejecutable al final. Se conserva íntegra su prosa y se renumeran los encabezados:

| Bloque original | Bloque adaptado |
| --- | --- |
| 1. De dónde viene esta práctica | 1, sin cambio |
| 2. Qué se busca con el ejercicio | 2, sin cambio |
| 3. Para qué sirve en el camino del alumno | 3, sin cambio |
| 5. El ritmo y la progresión de la práctica | 4 |
| 6. Los avisos de la fuente | 5 |
| 4. Cómo se hace, paso a paso | 6 |

Este reordenamiento está aprobado para EP-0.1. El principio editorial es recibir el contexto antes de ejecutar, pero no debe aplicarse una permutación fija a todas las prácticas ni desplazar cierres o avisos que necesiten otra posición. Conservar la correspondencia entre bloques originales y adaptados.

**Precaución al convertir el Markdown completo:** su `# EP-0.1…` y `**00. Presentación**` son cabecera documental. El HTML del cuerpo para `Guide` empieza en el primer `h2`. Enviar el `h1` al parser actual provoca fallback; convertir el rótulo de presentación en un párrafo añadiría una pantalla no prevista.

## 5. Contrato de pantallas y encabezados

La guía la deriva el servidor mediante [Guide](../../site/web/app/mu-plugins/cde-practicas/guide.php), a partir del HTML editorial. No hacer otra división independiente en el navegador o en un generador de voz.

- Cada encabezado tiene una pantalla, incluidos los padres. Antes de `1.1` aparece la portada `1. De dónde viene esta práctica`.
- En la pantalla de un hijo se muestra toda la ruta: padre + hijo; si hay tres niveles, los tres. Al cambiar de rama se descartan los descendientes de la anterior.
- La pantalla del hijo **sólo narra su último epígrafe**. El padre ya se ha narrado en su propia portada.
- Cada párrafo completo es una pantalla, aunque contenga varias frases subordinadas.
- EP-0.1 final: **49 pantallas = 6 portadas + 15 subepígrafes + 28 párrafos**. Las referencias históricas a 43 pantallas corresponden a la versión anterior sin portadas.
- Cada pantalla contiene `type`, `path`, `html`, `speech` y `section`. La guía también entrega `sections`, cuyos destinos son las portadas de los bloques principales. Los índices de pantalla y nombres de archivos empiezan en cero.
- La lectura completa usa las mismas secciones, con anclas únicas por instancia del visor y foco en el encabezado real.

El parser actual acepta encabezados `h2`–`h6`, párrafos con `em`, `strong`, `b`, `i`, `br` y enlaces, y envoltorios `div`, `section`, `article`. Ante listas, tablas, medios, bloques no soportados o texto suelto incompatible, devuelve una guía vacía y conserva la lectura completa. **No debe omitir instrucciones para aparentar una conversión correcta.** El exportador de audio rechaza una guía sin pantallas.

El avance es manual. «Activar audio» reproduce la pantalla actual y cada nueva pantalla mientras siga activo; cambiar de pantalla detiene el fragmento anterior y terminar una narración no avanza por sí solo. «Texto completo» pausa la voz y otra práctica o medio de la lección detiene la narración anterior. No generar una pista continua, tiempos de avance automáticos o progreso persistente suponiendo que ya forman parte del contrato del visor.

## 6. Texto exacto para el motor de voz

La separación entre texto visual y narrado ya está implementada en `Guide::headingSpeech()` y `numberSpeech()`. Reutilizarla y probarla; no delegar la interpretación de la numeración a Qwen3.

| Presentación visual | Texto enviado a TTS |
| --- | --- |
| `1. De dónde viene esta práctica` | `Uno. De dónde viene esta práctica.` |
| `1.1. En este corpus, canalizar es permitir que la energía pase` | `Uno punto uno. En este corpus, canalizar es permitir que la energía pase.` |
| `1.2. Una práctica que Tobías propuso a los asistentes` | `Uno punto dos. Una práctica que Tobías propuso a los asistentes.` |
| `2.2. Recibir un sentir antes que buscar una definición` | `Dos punto dos. Recibir un sentir antes que buscar una definición.` |
| Ruta visual `1` + `1.1` + `1.1.1. Título` | Sólo `Uno punto uno punto uno. Título.` |

Todos los epígrafes llegan con punto final al motor para cerrar la entonación. En pantalla siguen sin ese punto. La versión inicial dejó interpretar cifras al modelo: `1.1` se oyó como «1», `1.2` perdió la numeración y `2.2` sí se narraba; escribir los componentes en palabras resolvió esa inconsistencia.

Los componentes numéricos de hasta tres cifras se verbalizan como cardinales; los ceros iniciales o componentes más largos se leen dígito a dígito para no perder información. Los párrafos no pasan por esta conversión de numeración: su texto narrado procede del párrafo, sin etiquetas HTML y con espacios normalizados.

La preparación se hace **antes** de calcular la revisión y exportar el plan de voz. Un cambio de `speech`, de estructura o de orden invalida la revisión anterior. La regla actual reconoce prefijos numéricos; no afirmar que ya resuelve numeraciones alfabéticas o romanas de prácticas. Eso requerirá casos explícitos si aparece en nuevas fuentes.

## 7. Voz de Iván: instalación y parámetros usados

El generador reutilizable es [generate-practice-audio.py](../../scripts/generate-practice-audio.py). Usa la instalación existente en el Mac; no entrena otro modelo ni depende de voces del navegador, Ollama o un servicio HTTP dentro de WordPress.

Raíz de Contenidos en aitorFS: `/Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos`.

| Recurso | Valor utilizado |
| --- | --- |
| Entorno Python | `tmp/qwen3-tts-training/venv/bin/python`, relativo a Contenidos; Python 3.12.9 en la prueba registrada |
| Bundle de voz | `pruebas-voz/2026-09-23-entrenamiento-qwen3-ivan` |
| Modelo | `mlx-community/Qwen3-TTS-12Hz-1.7B-Base-bf16` |
| Snapshot local | `tmp/qwen3-tts-models/hub/models--mlx-community--Qwen3-TTS-12Hz-1.7B-Base-bf16/snapshots/a6eb4f68e4b056f1215157bb696209bc82a6db48` |
| Bibliotecas de la prueba | MLX 0.32.2; MLX Audio 0.5.5 |
| Selección | `seleccion-checkpoint.json`, dentro del bundle |
| Adaptador | `checkpoints/epoch-3/adapters.safetensors` |
| SHA-256 del adaptador | `02079a760772e103260f30331b6817efd2c189f4a727fca89eae041ad5ebbfb5` |
| Intensidad | `0.25` |
| Referencia | `F063.wav` y su `F063.txt` exacto, en `pruebas-voz/2026-09-23-seleccion-qwen3-tts-ucdm-0.5/dataset-aprobado/{audios,textos}/` |
| Salida | Mono, 24.000 Hz, WAV PCM de 16 bits y MP3 con `libmp3lame` a 96 kbit/s |

El generador lee `seleccion-checkpoint.json`, comprueba intensidad/referencia y la huella del adaptador, importa las funciones de carga de `entrenar.py` y escala los adaptadores LoRA por 0,25. No ejecuta su `main` de entrenamiento/evaluación. Las rutas de modelo y dataset dependen de la ubicación del bundle: copiar sólo `entrenar.py` a otro runtime no instala la voz.

Parámetros de inferencia existentes: `lang_code='Spanish'`, `temperature=0.7`, `repetition_penalty=1.5`, `top_k=50`, `top_p=1.0`; semilla `20261014 + índice_de_pantalla`; `max_tokens=max(350, ceil(n_palabras / 2.0 * 12.5 * 2.5))`. El proceso usa `HF_HUB_OFFLINE=1`, desactiva telemetría y bytecode y escribe en el directorio de salida indicado. Requiere `ffmpeg` y el entorno Apple Silicon/MLX disponible.

**Límite de aprobación:** la selección leída sigue indicando `candidato_para_escucha` y evaluación auditiva pendiente. La documentación histórica recoge uso y aprobación auditiva del candidato en el hilo, pero no se ha modificado ese estado operativo. Cada nueva generación conserva `audio_review: pending`; no convertir la aprobación de la voz o un ASR correcto en aprobación automática de todos los fragmentos nuevos.

## 8. Límite preciso del audio y eliminación del sonido residual

### Qué ocurrió realmente

Cada pantalla ya se generaba de forma independiente. **No se estaba cortando una narración larga por marcas de tiempo.** MLX/Qwen3 antepone códigos de la referencia de voz durante la decodificación y después retira ese tramo. La inspección comparó el recorte proporcional con el límite exacto: en tres muestras la diferencia fue de cero o una muestra, sólo 0,042 ms a 24 kHz. Eso no explicaba por sí solo el sonido de décimas de segundo señalado por el usuario. La hipótesis compatible con la evidencia fue arrastre del contexto acústico de F063, no un fragmento de la pantalla previa.

### Corrección implementada

1. Conservar F063 intacta. Preparar una copia `reference-F063-guarded.wav` en la salida.
2. Añadir **480 ms de silencio al final de esa copia**, equivalentes a 11.520 muestras mono a 24 kHz y seis frames del códec de 1.920 muestras. No añadir silencio al principio ni recortar 480 ms de la voz generada.
3. Generar cada pantalla en una llamada independiente, no streaming. Exigir exactamente un chunk y una decodificación capturada.
4. Capturar la salida completa de `speech_tokenizer.decode`, incluidos referencia y contenido generado.
5. Calcular el comienzo generado con los frames reales: `cut = (total_frames - generated_frames) * samples_per_frame`. El código toma el tamaño por frame de `decode_upsample_rate`; no lo deduce de una duración redondeada.
6. Verificar `0 < generated_frames <= total_frames` y `n_samples == total_frames * samples_per_frame`. Si no coincide, detener: no aplicar un recorte aproximado.
7. Guardar únicamente `raw[cut:]`. Verificar 24 kHz, muestras finitas y longitud válida; conservar duración, pico, número de muestras saturadas, RMS inicial y huellas en el JSON de cada pantalla.

No borrar un tiempo fijo al comienzo de la voz, no usar un umbral de volumen para cortar supuestos restos y no modificar la referencia original: esas estrategias pueden comerse el primer fonema. Si cambia MLX Audio o el códec, volver a validar dimensiones y frontera antes de aceptar nuevos audios.

### Evidencia y límites

En la regeneración con portadas y referencia protegida, los 49 límites fueron exactamente 270.720 muestras, equivalentes a 141 frames, y no hubo muestras saturadas. El RMS máximo de los primeros 30 ms fue aproximadamente 0,0000261 (−91,7 dBFS). Ese valor y ese límite pertenecen a esta referencia/ejecución; **no son constantes universales ni umbrales obligatorios para otras voces**.

Después de verbalizar la numeración se regeneraron los 21 encabezados y se reutilizaron 28 párrafos previa comprobación de texto, parámetros y SHA-256. Whisper local reconoció la numeración de los 21 encabezados; no se declaró fidelidad literal total porque había diferencias de puntuación y normalizaciones como «consciencia/conciencia». La versión final registrada suma 451,04 s. Las cifras de 437,6 s o 43 fragmentos en informes anteriores son de iteraciones distintas.

La prueba de referencia, las medidas acústicas y el ASR no sustituyen la escucha del arranque y el final de los fragmentos. Referencias: [historial técnico de voz](../practicas-cde-visor-y-voz.md), [resultados de numeración](../qa/practicas/desktop-20261014/numeracion-v3-resultados.json) y [cuatro pruebas de frontera](../../scripts/test_generate_practice_audio.py).

## 9. Plan, revisión, archivos y entrega de voz

El [exportador del plan](../../site/scripts/practice-audio-plan.php) requiere WP-CLI y permiso editorial. Lee el post, llama a `Guide::fromHtml(wpautop(post_content))` y emite `schema: cde.practice.audio-plan`, `version: 1`, `post_id`, `revision` y `screens`.

La revisión actual es SHA-256 del JSON de `screens` con Unicode y barras sin escapar, generado por WordPress antes de añadir URLs de audio. Usar la función canónica `Audio::revision`; no sustituirla por el hash del TXT, del archivo JSON indentado o de una serialización distinta en Python.

Comandos existentes, como referencia para el trabajo futuro; **no ejecutados al redactar este traspaso**:

```bash
# Desde site del entorno autorizado, WP-CLI en el host:
wp eval-file scripts/practice-audio-plan.php ID_PRACTICA --user=EDITOR > /ruta/plan.json

# Desde la raíz web que contiene el generador, con el Python del bundle:
/ruta/Contenidos/tmp/qwen3-tts-training/venv/bin/python scripts/generate-practice-audio.py /ruta/plan.json --voice-bundle /ruta/Contenidos/pruebas-voz/2026-09-23-entrenamiento-qwen3-ivan --output /ruta/salida-privada
```

Salida: `POST_ID/REVISION/0.mp3`, `0.wav`, `0.json`, etc., referencia protegida y `manifest.json`. Los metadatos por pantalla conservan texto, adaptador, intensidad, referencia original/preparada, padding, modo de recorte, semilla y hash del MP3. El generador sólo reutiliza un archivo de la misma salida si los metadatos de entrada y su hash coinciden exactamente. La reutilización entre revisiones realizada durante la corrección de encabezados no es una caché general implementada por el script.

El generador admite `--limit` para ensayos, pero escribe un manifiesto que contiene el plan completo y `audio_review: pending`: **la existencia de ese manifiesto no demuestra generación completa**. Comprobar todos los índices, archivos, metadatos y versiones antes de entregar. No usar una ejecución parcial como resultado aceptado.

La web sirve los MP3 desde `site/private/cde-practicas-audio/POST_ID/REVISION/`, fuera de `web/`, mediante [Audio](../../site/web/app/mu-plugins/cde-practicas/audio.php). En staging ese directorio se conserva mediante enlace al almacenamiento compartido `/srv/www/espaciosutil.org/shared/cde-practicas-audio`. El endpoint `?cde_practica_audio=1&practice=…&screen=…&revision=…` comprueba acceso, estado/contraseña, índice y revisión; responde `private, no-store`. No publicar URLs de archivos privados ni subirlos a una carpeta pública para facilitar su reproducción.

El audio se habilita en el visor cuando todos los MP3 de la revisión están presentes y no vacíos. Actualmente la web **no consulta `audio_review` ni valida los hashes de los metadatos al servirlos**; esa aceptación debe formar parte de la futura entrega automatizada. La revisión de contenido tampoco incluye la versión del modelo/voz: registrar por separado toda la configuración y huellas de síntesis para impedir mezclas inadvertidas. Preparar una entrega completa verificada fuera de la ruta activa antes de instalarla y comprobar después los bytes servidos.

Cambiar contenido o numeración exige otro plan; cambiar sólo de voz exige una política explícita de revisión/instalación, no asumir que el hash del texto lo detecta. Los IDs WordPress son específicos de entorno: generar el plan del destino o reconciliar explícitamente el ID antes de instalar los archivos.

## 10. Qué está disponible y qué falta unir

| Disponible | Alcance y límite |
| --- | --- |
| `Contenidos-worktrees/cde-practicas/scripts/cde_practicas.py` | Valida un inventario normalizado, fuentes, huellas y revisiones; emite `inventory-report.json` y `wordpress-package.json`, con cero escrituras remotas. **Su `render_text()` conserva listas `<ul>/<li>`; no realiza la adaptación a encabezados y párrafos descrita aquí.** |
| `schemas/cde/practices-inventory.schema.json` y `tests/test_cde_practicas.py` de esa rama | Contrato y regresiones del preparador antiguo; no equivalen a descubrimiento de prácticas dentro del intake de lección. |
| `.agents/skills/cde-practicas/SKILL.md` de esa rama | Candidata para inventario/importación supervisada. Expresamente separada del flujo completo; no se ha instalado en Paperclip. |
| [Importador WordPress](../../site/web/app/mu-plugins/cde-practicas/import.php) | Plan de lectura, revalidación previa, creación de borradores y adición de relaciones por clave. No publica prácticas, no sobrescribe fichas distintas, no retira relaciones por omisión ni escribe NocoDB. |
| `Guide`, plan de audio y generador | Producen pantallas y narraciones a partir de HTML ya adaptado. No detectan por sí solos los TXT de una carpeta de lección. |
| Visor, catálogo, ficha e incrustado | Ya desplegados en staging; no acreditan automatización editorial ni instalación del motor. |

Comandos del importador existentes: `wp cde-practicas plan paquete.json --out=plan-nuevo.json --user=ADMIN` y `wp cde-practicas apply plan-nuevo.json --user=ADMIN`. Aplicar modifica WordPress y requiere el alcance autorizado del procedimiento; no proponerlo como un comando de lectura.

Diferencias concretas que deben resolverse al integrar:

1. Convertir el outline con el criterio aprobado, conservar evidencia de correspondencia y entregar el HTML adaptado al paquete. El preparador antiguo produciría listas que `Guide` rechaza, dejando sólo lectura.
2. Reconciliar `referencia_fuente`: el contrato/importador antiguo aún exige ese campo como string, aunque el diseño vigente no lo exige ni lo muestra. Puede estar vacío en el contrato heredado; no inventar una referencia pública ni añadir de nuevo un campo obligatorio por copiar el preparador.
3. Reutilizar el resolver vigente de `clave_leccion`. El preparador y el importador antiguos sólo aceptan códigos numéricos, mientras que la skill operativa ya contempla segmentos como `0.7.a`; no introducir un segundo criterio de identidad incompatible.
4. Resolver la política de publicación de la práctica. **Crear y relacionar un borrador no basta: el incrustado excluye prácticas no publicadas.** La política vigente de lecciones `cde` distingue `draft` y `future` y prohíbe escribir `publish` para lecciones reales; no extender ni eludir esa regla para el CPT de prácticas sin definir su contrato. Evitar también que el catálogo revele antes de tiempo una práctica ligada a una lección programada.
5. Definir si una práctica sin guía adaptable o con audio pendiente permite cerrar alguna fase, y con qué estado explícito. El fallback de lectura de la web no es autorización para ignorar una capacidad requerida por el encargo.
6. Conectar el inventario remoto sólo mediante su esquema y política de campos reales. La tabla `cde_practicas` relacionada con `cde_lecciones` fue una propuesta, no una migración remota realizada. La referencia WordPress en la tabla de prácticas corresponde al post de la propia práctica; las referencias de los posts de lecciones permanecen en su tabla, sin duplicarlas. No crear filas/campos ni write-back fuera del contrato instalado.
7. Incorporar validación de plan, huellas de voz, completitud, revisión auditiva, instalación privada y readback; el generador de ensayo no es por sí solo un adaptador de producción desatendida.

## 11. Secuencia propuesta para la integración futura

Esto es una propuesta de implementación para la siguiente conversación, no una fase ya instalada ni una autorización de publicación en este documento.

1. **Intake:** resolver primero lección y carpeta con el flujo vigente; descubrir y registrar fuentes de prácticas, con ruta, hash, identidad y resultado explícito de presencia/ausencia/ambigüedad.
2. **Plan versionado:** declarar la capacidad de prácticas con el identificador que defina el contrato, sus entregables y dependencias. Si se detecta una práctica requerida y el runtime no sabe tratarla, bloquear con un diagnóstico útil; no omitirla para continuar.
3. **Adaptación editorial:** producir Markdown/HTML, mapa de correspondencia con el original, reordenamientos justificados y pruebas de cobertura. Mantener el TXT original intacto. Validar títulos, párrafos y casos no convertibles.
4. **Guía y voz:** obtener el mismo plan que usará la web, con numeración verbalizada, portadas y huellas; generar por pantalla y revisar integridad/texto/audio. La disponibilidad del Mac/MLX es una dependencia real del procedimiento: WordPress no genera esa voz en el servidor.
5. **WordPress en preparación:** resolver o crear la práctica por `clave_practica`, preservar trabajo editorial existente, añadir el Relationship sin borrar asociaciones y verificar la ficha y la relación inversa. El importador actual requiere que la lección ya exista: coordinarlo con la preparación en borrador de la fase WordPress.
6. **Instalación de audio y visibilidad:** entregar la revisión verificada con IDs del destino y aplicar la política acordada de publicación de prácticas. Concluir las escrituras preparatorias antes de la transición final `future` de la lección, que debe conservar su posición de última mutación de WordPress según el flujo vigente.
7. **Readback y reconciliación:** comprobar CPT, campos, contenido, Relationship, guía, audios protegidos y visualización real dentro de la lección. Sólo entonces registrar los hechos técnicos permitidos en inventario y aceptar los entregables.
8. **Cierre y recuperación:** usar fases, responsables, revisiones independientes, evidencias y reparaciones nativas del motor. Tras una interrupción, releer el estado para no duplicar posts, generar otra práctica o repetir una escritura de resultado incierto.

Que el contenido coincida no permite sobrescribir un post con identidad duplicada o texto diferente. Una práctica ya existente y verificada debe reutilizarse; una entrega revisada exige reconciliación de versión, no una segunda ficha por comodidad.

## 12. Pruebas que debe exigir la integración

- Detección: cero, una y varias prácticas; carpeta ilegible; fuentes en subcarpetas de otras lecciones; Unicode en nombres; mismo código EP en proyectos distintos; fuente cambiada durante la ejecución.
- Conversión: marcador/sangría real, índice inicial duplicado, metadatos fuera del cuerpo, encabezados de tres niveles, varias ideas hermanas, subordinadas profundas, citas y puntuación sin duplicaciones. Comprobar cobertura, orden dentro de cada bloque y ausencia de instrucciones añadidas.
- EP-0.1: fuente y adaptación con las huellas de este documento; 6 + 15 + 28 = 49 pantallas; cuerpo de ejecución al final. No copiar esa cantidad como expectativa para otras prácticas.
- Narración: punto final, cardinales y ceros iniciales, `Uno punto uno`, sólo el último epígrafe, cambio de hash cuando cambia `speech` y párrafos íntegros.
- Frontera acústica: las cuatro pruebas existentes de `test_generate_practice_audio.py`; referencia original intacta, 11.520 muestras de padding y rechazo de dimensiones incompatibles. Escucha/ASR de arranques y finales, números, omisiones y contaminaciones.
- Idempotencia: conservar relaciones ajenas; duplicados; práctica existente distinta; plan invalidado por cambios; interrupciones después de crear post/relacionar/copiar audio; metadatos corruptos, MP3 faltante o revisión antigua.
- Publicación: borrador que aún no aparece en la lección, programación y visibilidad acordada, ausencia de exposición prematura en catálogo, lector sin membresía, lector autorizado y endpoint privado sin caché.
- Motor: capacidad ausente, dependencias de voz no disponibles, revisión rechazada, reanudación y readback completo; las lecciones sin prácticas conservan su recorrido. Una nueva instalación no modifica los manifiestos que usan versiones anteriores.

Las 47 comprobaciones PHP y los cuatro tests Python citados en el historial corresponden a ejecuciones anteriores. Las pruebas móviles de staging acreditan interfaz y entrega de audio, **no** el nuevo flujo editorial. En este traspaso se han releído fuentes/código y contrastado huellas y estructura, sin regenerar voz, publicar contenidos ni ejecutar una lección.

## 13. Aislamiento, instalación y criterio de finalización

Desarrollar la integración en un worktree independiente con rama propia, partiendo de la versión adecuada del motor. Revisar Git y atribución antes de mover nada. No escribir en el checkout operativo, el catálogo de skills de Paperclip o la configuración instalada durante la recopilación o el desarrollo aislado. No recertificar para eludir el fallo de una lección ni asumir que una certificación nueva la migra.

La evolución debe incluir código, contrato, capacidad, skill, regresiones, certificación e instalación coordinada en el runtime inmutable que corresponda, siguiendo `docs/cde-flow-evolution.md` y el instalador vigente. La reserva/versionado y las condiciones actuales se verifican allí; no copiar comandos de instaladores históricos. Conservar la separación entre preparación, aceptación editorial/audio, escritura remota, publicación efectiva y lectura de comprobación.

La futura implementación estará completa cuando una lección resuelta con una práctica entregada en su carpeta produzca o reutilice una ficha correcta, su guía/narración aceptadas y su relación visible bajo la política acordada, y el motor registre el resultado observado con reanudación segura. Editar únicamente el texto de `SKILL.md`, crear un borrador oculto o generar un manifiesto de audio no satisface ese objetivo.

## 14. Mapa de referencias para la siguiente conversación

| Tema | Referencia |
| --- | --- |
| Comportamiento web vigente | [Contexto de prácticas](../context/practicas-cde.md) |
| Decisiones e inventario histórico | [Prácticas CDE](../practicas-cde.md) |
| Preparador/importador e integración diferida | [Implementación candidata](../practicas-cde-implementacion.md) |
| Instalación de voz, diagnóstico y sucesivas correcciones | [Visor y voz de Iván](../practicas-cde-visor-y-voz.md) |
| Guía, verbalización y fallback | [guide.php](../../site/web/app/mu-plugins/cde-practicas/guide.php) |
| CPT, ACF, acceso y relaciones | [catalog.php](../../site/web/app/mu-plugins/cde-practicas/catalog.php) |
| Plan/apply WordPress | [import.php](../../site/web/app/mu-plugins/cde-practicas/import.php) |
| Plan de narración | [practice-audio-plan.php](../../site/scripts/practice-audio-plan.php) |
| Generación y recorte exacto | [generate-practice-audio.py](../../scripts/generate-practice-audio.py) |
| Protección y revisión de audio | [audio.php](../../site/web/app/mu-plugins/cde-practicas/audio.php) |
| Tests de voz y guía | [Python](../../scripts/test_generate_practice_audio.py), [PHP](../../site/tests/cde-practicas-viewer.php) |
| Evidencia final de numeración | [Resultados JSON](../qa/practicas/desktop-20261014/numeracion-v3-resultados.json) |
| Estado publicado de web y audios | [Entrega de staging](../infraestructura/staging-practicas-presentaciones-2026-10-16.md), [runtime](../qa/practicas/mobile-20261016/runtime.json) |

Para abrir el siguiente trabajo puede usarse este encargo: «Integra la detección y preparación de prácticas en el flujo CDE versionado, tomando `docs/relevo/practicas-automatizacion-lecciones.md` como traspaso. Revisa primero el runtime y las ramas efectivos. Implementa en un worktree aislado el descubrimiento en la carpeta de la lección, la adaptación editorial, la guía/TTS de Iván, la relación ACF y las verificaciones/reanudación. Resuelve explícitamente los límites de visibilidad y publicación del CPT y las incompatibilidades del preparador antiguo. No instales ni migres lecciones operativas hasta la integración coordinada correspondiente».
