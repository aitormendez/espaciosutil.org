# Presentación instalada en WordPress local

Fecha: 2026-10-08. Lección: «Qué es El libro de Urantia», clave `cde:01:00:0.1`, post 2389. URL: http://espaciosutil.test/lecciones-del-cde/urantia/que-es-el-libro-de-urantia/. WordPress real en Lima, `WP_ENV=development`, base local con PMPro. Staging y producción no se modificaron.

## Resultado

15 diapositivas, 75 WebP de 640 a 3840 px, 16.401.212 bytes. Revisión activa: `d831299f008ae8d911eceec756eb8399c570f6b878e52d1efa887712f70159f3`. Antes de importar no existían `_cde_presentation_revision` ni `lesson_presentation_enabled`; después, puntero a esta revisión y activación `1`. No se alteraron el contenido editorial ni las membresías. El PDF permanece en Series CDE.

Almacén privado en macOS: `/Volumes/D/Desarrollo/Sites/espaciosutil.org/site/storage/cde-presentations/2389/d831299f008ae8d911eceec756eb8399c570f6b878e52d1efa887712f70159f3`. Lima ve esa misma carpeta bajo `/srv/www/espaciosutil.org/current/storage/cde-presentations`. La raíz de desarrollo se resuelve desde `WP_CONTENT_DIR`; no se modificaron secretos ni la configuración de la VM.

## Instalación y aislamiento

El desarrollo y los commits están en `/Users/aitor/.codex/worktrees/cde-presentaciones/espaciosutil.org`, rama `codex/cde-presentaciones-documentacion`. Implementación base: `b2e2b39`. Se aplicaron al checkout servido por Lima únicamente 16 archivos de runtime, además de sus assets compilados y el autoload de Composer. Los archivos existentes se compararon con el commit base `325fb19` antes de sustituirlos; se conservaron copias y hashes previos. Se preservaron los cambios ajenos de Tailscale/staging, mantenimiento y aislamiento de desarrollo. No se hizo merge ni commit en el checkout servido.

Copias previas y comprobación final: `tmp/presentation-local-install/original/`, `files.json`, `verified-files.json` y `build-before/`, dentro del worktree. Los 16 archivos servidos coinciden con los candidatos. Los assets anteriores no se borraron. Para retirar el piloto: desactivar la presentación, restaurar la ausencia original de ambos metadatos, restaurar únicamente los archivos de este overlay tras comparar sus hashes, restaurar el manifiesto de build y regenerar autoload/caché. Conservar el paquete hasta confirmar la retirada; no revertir todo el checkout.

Contenidos operativo, certificados, motor, skills, catálogo y configuración Paperclip permanecen intactos. La automatización del flujo de lecciones y la API para apps quedan para una etapa posterior. La configuración candidata de Trellis no se aplicó a Lima ni a servidores.

## Validación real

- WP-CLI ejecutado desde el host: dry-run, importación y activación de una única lección; repetición con revisión esperada mantiene el mismo puntero y paquete.
- Diez comprobaciones HTTP reales correctas: [resultados](http-wordpress-local.json). Miembro recibe WebP con bytes/hash exactos; anónimo 401 y cuenta sin acceso 403; cabeceras privadas/no-store/noindex; HEAD autorizado; revisión obsoleta 409; preview denegado a alumno y permitido a editor; ruta pública del almacén 404.
- Playwright/Chrome con sesiones temporales reales: 15 páginas, siguiente/contador, como máximo tres imágenes con fuente asignada, ampliación y solicitud 3840, Escape y restauración del foco. Anchos 390/768/1440 sin desbordamiento. Salida al índice y regreso a la lección con visor único y navegación funcional.
- Editor ACF: activación marcada y las 15 miniaturas privadas decodificadas correctamente. Se corrigió el nombre del campo de mensaje: `addMessage` añadía un sufijo que impedía ejecutar el filtro; `addField` conserva el nombre requerido. No se guardó el contenido de la lección desde el editor.
- Capturas inspeccionadas: `tmp/presentation-local-install/desktop.png`, `mobile-390.png`, `mobile-768.png`, `editor.png`. Scripts de QA local y resultados en esa carpeta privada. Las sesiones temporales se destruyen al cerrar la prueba, sin revocar sesiones existentes de los usuarios.
- Las 40 comprobaciones aisladas y el build previo constan en [piloto](piloto.md). Esta validación añade WordPress, cookies y membresías reales; no equivale a validar dispositivos iOS/Android físicos.

## Límites observados

La copia local devuelve 404 en el endpoint previo `video-resolutions` y el reproductor registra error de metadata Bunny. No lo causa el visor; vídeo y quiz completos siguen sin validación integral en este entorno. No se alteró el aislamiento local que bloquea comunicaciones externas.

El Mac no resolvía `espaciosutil.test` mediante `/etc/hosts`. Las pruebas usaron la IP observada de Lima `192.168.64.19`, con `curl --resolve` y una regla de resolución en Chrome. Se solicitó a Aitor añadir la entrada de hosts porque macOS exige su contraseña de administrador. Esa modificación no se realizó automáticamente y la IP debe volver a comprobarse si reinicia/cambia la VM.

## Navegación por teclado en la vista normal

Las flechas izquierda/derecha accionan los mismos botones de anterior/siguiente cuando el centro del visor está visible, sin requerir foco previo en el componente. En el diálogo ampliado siguen funcionando, también con zoom. Se respetan campos de texto, otros controles con foco, combinaciones con modificadores y eventos ya atendidos. El listener se elimina al destruir el visor con Barba. Los botones anuncian sus atajos con `aria-keyshortcuts` y un título.

Validado en WordPress local con Playwright: avanzar/retroceder sin foco, límite inicial, Shift, comentario enfocado, visor fuera de pantalla, diálogo, zoom y retorno mediante Barba sin duplicar movimientos. Build Vite correcto. Los dos archivos y assets se aplicaron exclusivamente al piloto local, con copia previa en `tmp/presentation-local-install/keyboard-before/`.

## Controles con Tabler Icons

Se reutiliza `x-dynamic-component` del tema con los componentes de `secondnetwork/blade-tabler-icons`: chevron-left/right, arrows-maximize, x, zoom-in/out y refresh. Los botones conservan sus nombres accesibles y atajos; el zoom alterna iconos y etiqueta sin sustituir el SVG por texto. Tamaño visual 20 px y área de interacción mínima 44 px. Build correcto y comprobación en WordPress real de siete SVG, navegación, diálogo, ambos estados de zoom y móvil de 390 px sin desbordamiento. Captura: `tmp/presentation-local-install/icons-mobile.png`. Copia previa local: `tmp/presentation-local-install/icons-before/`.
