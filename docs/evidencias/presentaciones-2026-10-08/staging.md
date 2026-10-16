# Despliegue del piloto de presentaciones en staging

Fecha: 2026-10-08. Resultado: desplegado con Trellis, importado, activado y validado. URL: https://stage.espaciosutil.org/lecciones-del-cde/urantia/que-es-el-libro-de-urantia/.

## Código y release

Rama pública: `codex/cde-presentaciones-staging`. Commit: `8044d9fad3b9e3af165f0f59ac0aa98b77667398`. Worktree de despliegue: `/private/tmp/cde-presentaciones-staging-20261008`, limpio antes y después de desplegar. Se preparó desde `main` con los 23 archivos de código/configuración/pruebas, sin incorporar el historial de informes y medios de la rama documental. Publicación expresamente autorizada por Aitor.

Comando ejecutado: `trellis deploy --branch=codex/cde-presentaciones-staging staging`, desde `trellis`, con un ejecutable SSH temporal que añade `-J web@134.209.21.17`. El acceso directo a staging seguía bloqueado por su ruta Tailscale; no se modificaron firewall, rutas ni servidores para resolverlo. El primer intento no hizo cambios remotos; el segundo terminó con `ok=58`, `changed=29`, `unreachable=0`, `failed=0`.

`current` apunta a `/srv/www/espaciosutil.org/releases/20261008105351`. Anterior: `/srv/www/espaciosutil.org/releases/20261001070650`. Los 15 archivos de runtime coinciden por SHA-256 con el candidato: [comprobación](staging-runtime-verification.json). Credenciales temporales de Composer retiradas. El aislamiento de staging permanece instalado.

El checkout principal conserva su overlay local y cambios ajenos sin revertirlos ni confirmarlos. Contenidos, certificados, motor, skills y configuración Paperclip no se modificaron. Producción se utilizó solo como tránsito SSH, sin cambios de aplicación ni configuración.

## Datos y rollback

Backup privado previo: `/srv/www/espaciosutil.org/shared/cde-presentations-backups/pre-presentaciones-20261008.sql`, modo 0600, `web:www-data`. Antes de la importación no había revisión ni activación para 2389. No se modificaron permisos ni membresías.

La importación única corresponde a `cde:01:00:0.1`, 15 páginas y 75 WebP hasta 3840 px. Revisión activa: `d831299f008ae8d911eceec756eb8399c570f6b878e52d1efa887712f70159f3`. Se ejecutaron dry-run, importación/activación y repetición con revisión esperada; la repetición conserva el mismo paquete. El PDF no se subió.

Almacén persistente: `/srv/www/espaciosutil.org/shared/cde-presentations`, modo 0750, `web:www-data`. El paquete de entrada permanece fuera del directorio público en `shared/cde-presentations-input/urantia-20261008`. Si es necesario retirar solo el piloto, desactivar y restaurar la ausencia previa de `_cde_presentation_revision` y `lesson_presentation_enabled`; conservar archivos para recuperación. Trellis permite volver a la release anterior. No restaurar toda la base si hubo cambios posteriores legítimos.

## Validación

- 14 pruebas de conversión/paquete y 12 comprobaciones del importador; Pint y sintaxis de Trellis correctos sobre el candidato. Builds de Sage y del plugin de bloques completados por Trellis.
- Diez comprobaciones HTTPS reales: [resultados](staging-http-real-results.json). Miembro 200, anónimo 401, cuenta sin acceso 403, hashes/bytes exactos, cabeceras privadas/no-store/noindex, HEAD, revisión obsoleta 409, preview editorial y ruta pública 404. Las cabeceras se comparan sin distinguir mayúsculas, compatible con HTTP/2.
- Playwright: 15 diapositivas, iconos Tabler, flechas de teclado, carga acotada a tres imágenes, zoom que solicita 3840, Escape/foco, móvil 390 px sin desbordamiento y retorno con Barba sin duplicar visor. Se espera el evento asíncrono de cierre antes de comprobar el foco.
- Editor real: activación marcada y las 15 miniaturas decodificadas. Sin guardar cambios editoriales. Sesiones temporales de QA revocadas y sus archivos de cookies retirados al terminar.
- Capturas y log completo conservados localmente en `tmp/presentation-local-install/staging/` de la rama documental, sin versionar medios.

## Próximos despliegues

La rama todavía no está integrada en `main`. Para conservar la funcionalidad al desplegar de nuevo hay que seleccionar `--branch=codex/cde-presentaciones-staging` o integrar antes el commit de implementación de forma coordinada. Un despliegue predeterminado de `main` retiraría el visor del código activo. No se ha desplegado producción ni implementado la API de apps ni la automatización de Contenidos.

## Corrección de ancho del 2026-10-14

Staging pasa a la rama `codex/cde-presentaciones-ancho`, commit `7da14d8`, release `20261014150626`. Esta referencia sustituye a la rama indicada arriba para futuros despliegues. El slider medía 928 px frente a 896 px del vídeo y el índice. Se usa ahora el token `--container-4xl` (56 rem), sin padding interno adicional, con márgenes iguales al vídeo: 16 px en móvil y 24 px desde 768 px. El cambio también se aplicó y compiló en local preservando sus archivos ajenos.

Validación real en staging tras Trellis: mismo ancho y posición horizontal que el vídeo en cargas nuevas de 390, 768, 1024 y 1440 px; mismo ancho que el índice en escritorio; diálogo ampliado a todo el viewport. Trellis terminó sin errores y las sesiones temporales se revocaron. Worktree de despliegue limpio: `/Users/aitor/.codex/worktrees/cde-presentaciones-ancho/espaciosutil.org`. La comprobación recarga por tamaño porque el reproductor conserva su modo ancho tras cambiar desde móvil. Evidencia visual local: `tmp/presentation-local-install/ancho-20261014/alineado.png`.

## Indicador de carga del 2026-10-14

Staging conserva la rama `codex/cde-presentaciones-ancho` y pasa a los commits `02d539a` y `ac1186e`, release `20261014160035`. El visor muestra un icono Tabler animado y el texto «Cargando diapositiva…» mientras obtiene la presentación o decodifica la imagen activa, también al navegar y solicitar 4K. El indicador se oculta cuando la imagen está lista o se muestra un error recuperable. Incluye estado accesible y respeta movimiento reducido. Los errores de imágenes anteriores se ignoran durante la renovación del manifiesto para evitar que interfieran con el reintento.

Validación local y en staging mediante Playwright: imágenes demoradas 1,8 segundos, navegación rápida, ampliación y zoom a 3840 px, preferencia de movimiento reducido, errores simultáneos con manifiesto demorado y recuperación manual sin indicador infinito ni botón de reintento residual. Build local correcto y Trellis terminado con `ok=58`, `changed=28`, `unreachable=0`, `failed=0`. El SHA-256 del JavaScript remoto coincide con el commit desplegado. Las sesiones temporales de QA se revocaron. Scripts, capturas y logs locales: `tmp/presentation-local-install/loading-20261014/`.

Archivos funcionales afectados: `resources/js/lessons/presentation.js`, `resources/css/layouts/presentation.css` y `resources/views/partials/lesson-presentation.blade.php`, dentro del tema Sage. No requiere cambios de datos, permisos, motor CDE, skills ni instalación de paquetes de presentaciones. Se mantiene el overlay local y la separación del checkout de Contenidos.

## Fondo de espera uniforme del 2026-10-14

Desplegado el commit `36b30ec` de `codex/cde-presentaciones-ancho`, release `20261014163846`. La capa de carga tiene ahora fondo opaco `#171321`, compartido con los controles, el visor y el diálogo mediante `--presentation-background`. Validación local con imágenes demoradas 2,5 segundos en modo normal y ampliado: color calculado uniforme y retirada del indicador al cargar. Build correcto; Trellis finaliza con `failed=0` y el CSS remoto coincide por SHA-256 con el candidato. Sesiones temporales revocadas. Evidencia local: `tmp/presentation-local-install/fondo-20261014/`.

## Pestaña del editor del 2026-10-14

Commit de despliegue `1431c8d`, rama `codex/cde-presentaciones-ancho`, release `20261014172316`. Presentación pasa a ser una pestaña de «Lección CDE», entre Medios y Cuestionario. Se elimina el grupo independiente `CdePresentation` y se conservan las claves originales de ACF mediante `setCustomKey`, los metadatos de activación y los paquetes importados. Un filtro mantiene la exclusión de estos campos en REST, ya que el grupo de destino sí expone sus demás campos.

Antes de corregirlo se confirmó en el navegador local que el grupo separado estaba registrado y mostraba las 15 miniaturas de Urantia; no se reprodujo su ausencia ni se detectó que faltara su archivo en el checkout servido. Tras la corrección se validaron los editores HTTP reales de local y staging: pestaña dentro del grupo correcto, activación conservada, 15 miniaturas decodificadas y ausencia del panel separado. Prueba reutilizable de solo lectura: `wp eval-file tests/cde-presentation-fields-live.php` desde `site`; comprueba integración, referencias compatibles y exclusión REST sin alterar los demás campos. Pasó en ambos entornos, junto con sintaxis PHP y `git diff --check`. Trellis terminó con `failed=0`. No requiere cambios del motor, skills, certificados ni datos de presentaciones.

## Diferencia entre el tema local y la vista previa de Prácticas — 2026-10-15

La captura del editor sin Presentación correspondía a la vista previa optativa de Prácticas. El cargador local ignorado `site/web/app/mu-plugins/espaciosutil-practicas-local-preview.php` cambia el tema a `sage-practicas-preview` cuando el navegador tiene la cookie `cde_practicas_preview=1` (o la CLI recibe `CDE_PRACTICAS_PREVIEW=1`). Ese tema apunta a `site/tmp/cde-practicas-preview/theme`, una copia distinta de Sage sin la pestaña nueva. El tema normal sí la tenía; la comprobación del día anterior no cubría esta cookie. DNS, raíz Nginx y montaje Lima se verificaron y apuntan al checkout esperado.

Se añadió únicamente el bloque de la pestaña Presentación al `app/Fields/Cde.php` de la copia local de vista previa, conservando Prácticas y todos sus cambios. Respaldo: `site/tmp/cde-practicas-preview/Cde.before-presentacion-20261015.php`. El [parche local](local-preview-tab-20261015.patch) permite revisar o reaplicar el ajuste si esa copia se regenera desde una rama que aún no incluye la presentación; verificar antes con `git apply --check`. No se modificó la rama de Prácticas ni se desplegó staging o producción.

Validación: sintaxis PHP y `CDE_PRACTICAS_PREVIEW=1 wp eval-file tests/cde-presentation-fields-live.php` desde `site`; editor HTTP de Urantia con la cookie de vista previa activada, ambas pestañas presentes, activación conservada y 15 miniaturas decodificadas. Las pruebas futuras del editor local deben distinguir el tema normal de esta vista previa optativa.

## Frontal de Presentación en la vista previa de Prácticas — 2026-10-15

La incorporación de la pestaña no bastaba: la copia del tema de Prácticas tampoco incluía el composer, parcial, CSS ni inicialización y limpieza del visor. Se incorporaron de forma aditiva los seis puntos de integración y los tres archivos del visor ya validados, manteniendo intactos los archivos y las llamadas de Prácticas. Se compiló la copia con `npm run build -- --base=/app/themes/sage-practicas-preview/public/build/` y se limpiaron sus vistas compiladas. El enlace local `node_modules` reutiliza las dependencias instaladas del tema principal; la salida se genera únicamente en la copia de vista previa. Fuentes y build previos respaldados en `site/tmp/cde-practicas-preview/backups/frontend-presentacion-20261015/`.

[Parche del frontal](local-preview-front-20261015.patch), complementario al de la pestaña. Validación HTTP real con la cookie de Prácticas activada: 15 diapositivas, avance y retroceso, ampliación y carga de 3840 px, vuelta a la lección mediante navegación Barba sin duplicación, y avance del visor de Prácticas en la misma lección de Urantia, que en ese momento tiene asociada la práctica 2998. No se modificaron asociaciones ni permisos. El build y la sintaxis PHP pasan. Se revocaron las sesiones temporales de prueba. Evidencias locales en `tmp/presentation-local-install/preview-front-20261015/`. No hubo despliegue ni cambios de las ramas de otros trabajos.
