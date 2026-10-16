# Piloto de presentación de Urantia

Fecha: 2026-10-08. Estado: implementado, importado y activado en WordPress local real (`espaciosutil.test`, Lima), por indicación posterior de Aitor. La etapa de una lección local está completada y el piloto también está desplegado y validado en staging. [Informe de staging](staging.md). [Instalación y validación local](wordpress-local.md).

## Alcance y ubicación

Worktree: `/Users/aitor/.codex/worktrees/cde-presentaciones/espaciosutil.org`. Rama: `codex/cde-presentaciones-documentacion`. Desarrollo separado de `main` y de Contenidos. Los cambios ajenos observados en el checkout web compartido (reglas Tailscale/staging y mantenimiento) se conservaron. No se modificaron motor, certificados, skills, catálogo Paperclip, NocoDB ni originales de Series CDE.

Piloto: `cde:01:00:0.1`, WordPress staging 2389. La consulta HTTPS de lectura confirmó título, publicación y URL: https://stage.espaciosutil.org/lecciones-del-cde/urantia/que-es-el-libro-de-urantia/.

## Implementación

- Conversor local Python/Poppler: 15 páginas, 75 variantes WebP hasta 3840 × 2160, sin texto extraído ni PDF en el paquete. Paquete privado local de 16.401.212 bytes. Fuente/revisión: [recibo](conversion.json).
- Mu-plugin: validación de paquete, importador WP-CLI con dry-run, clave única, bloqueo, comparación de revisión, instalación antes de activar, repetición inocua y rollback por revisión conservada.
- Autorización por la lección existente en cada solicitud; almacén privado, sin adjuntos públicos; MIME/dimensiones/huellas comprobados antes de servir; no-store y HEAD sin contenido. Previsualización editorial separada.
- Sage/ACF: visor tras el reproductor, grupo privado con activación y miniaturas, navegación, zoom, diálogo, foco/Escape, carga acotada y ciclo de vida Barba.
- Configuración candidata de Trellis: raíz privada persistente y variable de entorno, solo staging. No cambia reglas de red.

La entrega binaria del piloto usa PHP después de autorizar y verificar el archivo. X-Accel-Redirect y el endpoint Bearer de las apps quedan pendientes. La API web evita depender de nonces REST en los atributos `srcset` de imágenes autenticadas por cookie.

## Validación ejecutada

- 14 pruebas Python/PHP del paquete y conversor: determinismo, no ampliar fuente rasterizada, PDF inválido, imagen corrupta, PDF disfrazado, fuente extra, ausencia de imagen, traversal, enlaces, variantes repetidas, geometría, orden, campos de texto y manifiesto alterado. [Salida](package-tests.txt).
- 14 pruebas HTTP sobre el controlador y Access reales con primitivas WordPress simuladas: miembro, anónimo, permiso denegado, borrador, PMPro ausente, lección pública, imagen privada, copia de URL, HEAD, condicional sin sesión, revisión obsoleta, preview editorial, preview alumno, método POST y raíz no pública. [Salida](http-tests.txt).
- 12 comprobaciones del importador real con almacenamiento temporal y primitivas WP simuladas: dry-run sin escritura, clave duplicada, activación, repetición, revisión concurrente, bloqueo, sustitución, rollback y rechazo de paquete roto sin alterar puntero. [Salida](import-tests.txt).
- Build Vite correcto. Avisos existentes de chunks grandes y directivas de Vidstack; no se han presentado como errores del visor.
- Playwright/Chromium: 390, 768 y 1440 px sin desbordamiento; diálogo horizontal 844 × 390; navegación/contador; máximo tres imágenes asignadas; petición de variante 3840 al ampliar con zoom; Escape y restauración de foco; tres ciclos de destruir/montar; sesión caducada, mensaje y reintento; una diapositiva con ambos extremos desactivados. Capturas privadas en `tmp/presentation-pilot/`. Las capturas de zoom deben esperar decodificación y transición antes de evaluar píxeles.
- Inspección de hoja de contacto de las 15 páginas y muestras ampliadas. No constituye revisión editorial exhaustiva. El navegador de QA usa el parcial Blade y el módulo JS reales, pero no una página WordPress completa.

## Antecedente: acceso a staging

HTTPS staging responde 200. SSH a `138.68.135.185:22` y `100.69.97.44:22` agota el tiempo. La ruta local de `138.68.135.185` pasa por Tailscale (`utun4`); la configuración local del cambio de red observado permite únicamente 443 en `tailscale0` y descarta el resto. No se cambiaron rutas, firewall ni configuración de Tailscale. Se consultó al usuario por la vía de administración vigente mientras se completaba la implementación local.

## Próximos pasos para staging (fuera de la etapa local)

1. Resolver la vía de administración de staging y confirmar su release vivo e integraciones aisladas antes de desplegar. No mezclar cambios ajenos en una promoción del checkout compartido.
2. Desplegar la rama mediante el proceso vigente, preservando el release anterior; verificar variable y directorio privado. Registrar revisión/activación previa de 2389.
3. Transferir solo paquete, ejecutar dry-run, importar y activar exclusivamente `cde:01:00:0.1`.
4. Verificar en WordPress real acceso autorizado/denegado, imágenes, ACF, Barba, vídeo/quiz, caché y tamaños; registrar URL y evidencias. Solo entonces cerrar esta etapa.

Procedimiento y comandos: [README del piloto](../../../scripts/cde-presentations/README.md).
