# Prácticas: visor de escritorio y voz de Iván

Fecha: 2026-10-14. Rama: `codex/cde-practicas`. Worktree web en aitorFS: `/Users/aitor/.codex/worktrees/cde-practicas/espaciosutil.org`. Implementación candidata, sin merge, push, despliegue, instalación de skills ni recertificación del motor.

## Comportamiento implementado

Catálogo oscuro con búsqueda, filtro por serie de las lecciones y un enlace por lección, usando únicamente su título. Una práctica puede aparecer en varias series a través de sus relaciones. No hay clasificación propia ni referencia al texto fuente en la ficha. El catálogo, la ficha individual y el incrustado se apoyan en el mismo CPT y Relationship.

`partials/practice-viewer.blade.php` es el visor compartido y `resources/js/practices/viewer.js` controla cada instancia. Cada ruta de encabezados inicia su bloque; cada párrafo es una pantalla. EP-0.1 tiene 43 pantallas, 6 bloques y ejecución al final. No se han añadido portadas de padres ni progreso persistente. Un texto con listas, tablas o medios sin adaptar conserva la lectura completa, sin omitir instrucciones para forzar la conversión.

La guía se calcula en el servidor desde el HTML editorial mediante `Guide`; el navegador no divide el DOM ni inventa resúmenes. La prosa inline se sanea antes de insertarla. El formato actual permite probar la web; la API de pantallas y los IDs estables para la app aún deben diseñarse. La lectura completa funciona sin JavaScript.

«Activar audio» reproduce la pantalla actual y cada nueva pantalla hasta desactivarlo. Cambiar de pantalla corta el fragmento previo; terminar un audio no avanza. Activar otra práctica o un medio de la lección detiene la narración anterior. «Texto completo» la pausa y regresar a la guía mantiene la pantalla. No se usan las voces del navegador.

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
