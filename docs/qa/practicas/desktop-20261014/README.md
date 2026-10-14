# Prácticas de escritorio: validación local del 2026-10-14

Copia optativa en la VM `espaciosutil.test`, WordPress y MySQL reales. Tema compilado desde el worktree `codex/cde-practicas`; no se cambia el tema global. Contenido EP-0.1 de la adaptación Markdown; lección y membresía sintéticas. Los límites del piloto y su retirada están descritos en [visor y voz](../../../practicas-cde-visor-y-voz.md).

## Resultados

- `site/tests/cde-practicas-viewer.php`: 32 comprobaciones correctas sin escrituras en la base de datos.
- `site/tests/cde-practicas-browser.js`: recorrido completo sin rechazo de aserciones. Reproducción real, cambio de fragmento al avanzar, entrada/salida con Escape, índice oculto/visible, ausencia de reinicio al cambiar a pantalla completa, lectura completa y regreso a la misma pantalla, parada manual, límite de 43 pantallas y retroceso. Se esperó al final real del fragmento de la pantalla 42 y no hubo avance automático.
- HTTP: miembro obtiene MP3 200 y `private, no-store`; usuario anónimo con la vista previa activada obtiene 403 tanto en ficha como en audio, sin guía entregada. Huella antigua e índice inválido devuelven 404.
- Formulario de catálogo: consulta sin coincidencias y búsqueda «suelo» en la serie de la lección, con un único enlace de contexto, correctas.
- Incrustado: índice inicialmente oculto, reproducción y siguiente correctos; al abrir la ficha la reproducción anterior queda detenida. Sin desbordamiento horizontal a 1440 px.
- Vite, directivas Blade, sintaxis PHP y Python, y `git diff --check` correctos. Avisos preexistentes de Vidstack/chunks y de constantes PDO deprecadas en PHP 8.5.
- Generación local: 43 fragmentos, 482,4 s, 240,43 s de inferencia y cero muestras saturadas. No se atribuye a estas métricas una aprobación perceptiva ni fidelidad textual verificada por transcripción.

La primera petición al antiguo endpoint administrativo falló por una comprobación de actualizaciones bloqueada por el aislamiento de red de la VM. Se sustituyó por una ruta frontend protegida y se repitió el recorrido con reproducción correcta. Se corrigió también el tratamiento explícito de Escape. Los errores esperados 404 del test no son fallos del visor. Matomo no resuelve en la VM; su error permanece fuera del alcance.

## Repetición

Desde el host, en el directorio `site` del piloto, con el cargador local habilitado y los datos de prueba identificados:

```bash
CDE_PRACTICAS_PREVIEW=1 wp eval-file /ruta/worktree/site/tests/cde-practicas-viewer.php ID_PRACTICA ID_LECCION ID_MIEMBRO
```

La prueba PHP valida expresamente la muestra EP-0.1 (43 pantallas, 6 bloques, audio completo). No crea datos ni modifica membresías. El script del navegador requiere sesión de miembro en la ficha y el siguiente hook de observación instalado con `page.addInitScript` antes de cargarla:

```javascript
const NativeAudio = window.Audio;
window.__practiceAudio = [];
window.Audio = class extends NativeAudio {
  constructor(...args) { super(...args); window.__practiceAudio.push(this); }
};
```

Este hook vive sólo en el navegador de QA y no forma parte del producto. El script devuelve las aserciones superadas y los estados HTTP. No guardar cookies ni credenciales en Git.

## Capturas

- [Catálogo con búsqueda y filtro de serie](practicas-catalogo.png).
- [Visor incrustado en la lección](practicas-incrustado.png).
- [Ficha individual](practicas-individual.png).
- [Pantalla completa](practicas-fullscreen.png).

Móvil, Safari/iOS/Android, cachés de staging/producción, almacenamiento remoto y la escucha editorial quedan pendientes de sus fases correspondientes. Esta prueba no integra las ramas ni instala skills.

## Segunda iteración: portadas, voz e índice en lectura

La versión vigente incorpora 49 pantallas y sustituye el recorrido de 43 de la primera prueba. Las regresiones actuales están en `site/tests/cde-practicas-viewer.php`, `site/tests/cde-practicas-adjustments-browser.js` y `scripts/test_generate_practice_audio.py`. Se comprueban portadas, ruta visual, voz sólo del último epígrafe con punto final, anclas de lectura por instancia y límite de audio por frames exactos.

La prueba del índice llevó el foco al encabezado 6 sin abandonar la pestaña de lectura; en pantalla completa el scroll interno llegó a 3.138 px, con el encabezado a 209,94 px por debajo de la barra que termina a 178 px, sin desbordamiento del visor. Volver a la guía conservó la pantalla 2.

- [Portada del bloque](practicas-portada-v2.png).
- [Índice en lectura completa](practicas-lectura-indice-v2.png).
- [Lectura e índice en pantalla completa](practicas-texto-completo-fullscreen-v2.png).

El diagnóstico y tratamiento de la referencia acústica se documentan en [visor y voz](../../../practicas-cde-visor-y-voz.md). La validación automática no sustituye la escucha humana del inicio de los fragmentos.

La regresión funcional final del visor pasó sus 17 aserciones con los 49 audios preparados; [resultado HTTP y funcional](ajustes-v2-resultados.json). También se comprobó el índice de lectura en la práctica incrustada: visible, destino correcto al encabezado 6 y sin desbordamiento horizontal. Compilación estándar Vite correcta.
