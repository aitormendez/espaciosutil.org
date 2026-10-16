# Presentaciones CDE: plan de implementación en staging

Fecha del plan: 2026-10-01. Actualización 2026-10-08: piloto web de una lección instalado, activado y validado en WordPress local real (Lima), por indicación de Aitor; piloto de Urantia desplegado y validado también en staging (commit `8044d9f`). [Despliegue](../evidencias/presentaciones-2026-10-08/staging.md). [Informe del piloto](../evidencias/presentaciones-2026-10-08/piloto.md). La entrega inicial usa PHP autenticado; X-Accel-Redirect y API móvil se aplazan. Estado del inventario: inventario implementado y probado en una rama aislada, sin integrar ni instalar. Los ocho PDF ya se trasladaron a `Series CDE`; Aitor creó el campo NocoDB. Conversión local, PDF fuera del servidor y acceso heredado de la lección acordados. Contrato: [presentaciones v1](../cde-presentaciones-contrato.md). El piloto está desplegado en staging; producción y las rutas de NocoDB no se han modificado.

Actualización 2026-10-15: [editor ACF editable](../cde-presentaciones-editor.md) implementado y probado solo en local. Su despliegue y la integración automatizada quedan pendientes.

## Objetivo y criterio de cierre

Entregar en `stage.espaciosutil.org` un pase de diapositivas derivado del PDF definitivo de cada lección, legible con ampliación, protegido según su acceso y reutilizable por futuras apps. El sistema se considera validado cuando funciona con un PDF con texto y un fixture rasterizado, el lote autorizado queda identificado sin ambigüedad, una segunda importación es inocua y se demuestra sustitución/rollback sin filtración de archivos privados.

El piloto puede validarse con las cuatro lecciones ya presentes. No se dará por terminado el lote de ocho mientras falten las cuatro correspondencias restantes de staging. Su ausencia no bloquea construir y probar el sistema con Urantia y Un curso de milagros.

## Fase 0. Fijar evidencias y destinos

1. Refrescar estado de Git y preservar los archivos ajenos. Desarrollar en un worktree independiente con rama propia; una rama diferente dentro del checkout operativo no aporta aislamiento. Revisar la base vigente antes de integrar y conservar la versión de ejecución de las lecciones activas. No atribuir el SHA local al release desplegado sin evidencia.
2. Identificar release y hostname de staging. Confirmar aislamiento de correo, pagos, analítica, cron y automatismos con las comprobaciones existentes. No copiar la base de producción sobre staging para incorporar cuatro lecciones.
3. Volver a leer por clave las ocho filas NocoDB y los posts de staging. Destinos actualmente verificados: Urantia 2389, Seth 2586, Un curso de milagros 2770 y Ramtha 2814. Series 05–08 no tienen correspondencia actual; preparar una importación focal de sus lecciones, medios y permisos según el mecanismo del proyecto y obtener nuevos IDs de staging por lectura. No asumir igualdad de IDs entre entornos.
4. Conservar los ocho hashes, rutas de entrega y aviso de Iván como evidencia de recepción. Resolver y verificar la ubicación final de cada PDF en su lección dentro de `Series CDE` antes de registrar `presentacion_pdf_ruta` o convertirlo. Si aún falta el traslado, dejarlo como preparación pendiente; no rellenar el campo con Dropbox ni con una ruta futura inexistente. Si cambia algún PDF, recalcular evidencia y tratarlo como revisión nueva antes de importar.
5. Confirmar la capacidad local de Poppler y WebP. En esta auditoría están disponibles `pdfinfo`, `pdftoppm`, `pdftotext` y `pdffonts`; el runtime de Python de Codex dispone de Pillow. No instalar conversores en el VPS por defecto.

Salida: manifiesto de entradas y mapeo por entorno, con cualquier lección pendiente claramente señalada.

## Fase 1. Construir el dominio y el conversor

- Crear esquema validable del paquete y fixtures pequeños: con texto, rasterizado, página única, geometría distinta y PDF inválido. El ejemplo del contrato es ilustrativo, no debe usarse como fixture válido de hash/revisión.
- Crear conversor local con parámetros explícitos de PDF, clave y salida. Preflight, límites, variantes, hashes, perfil versionado y hoja de contacto. No extraer texto ni ejecutar OCR: las diapositivas se acompañan de los resúmenes ya existentes en la lección, sin campos de transcripción en el manifiesto o la API. Preservar el PDF definitivo por hash en un archivo local separado del directorio exportable. El paquete remoto incluye solo manifiesto, imágenes y metadatos de uso; nunca PDF, PPTX ni informes internos. No editar ni renombrar originales.
- Probar con Urantia y Un curso de milagros. Comparar páginas densas en 1280 y 2560 cuando proceda; elegir perfil de compresión por nitidez y peso. Las dos fuentes piloto actuales tienen texto y fuentes; usar un fixture rasterizado separado para comprobar que no se generan variantes ampliadas ficticias.
- Comprobar las 15 páginas por paquete, todos los hashes y la consistencia de dimensiones; inspección visual completa de los derivados del piloto. Para los seis restantes, revisar todas las miniaturas y a resolución de lectura las páginas densas o sospechosas.
- Diseñar identidad de revisión independiente del entorno y sin timestamps volátiles; generar dos veces el mismo fixture para verificar determinismo.

Salida: dos paquetes piloto reproducibles y contrato de manifiesto validado.

## Fase 2. Importación y backend WordPress

Archivos propuestos:

- `site/web/app/mu-plugins/espaciosutil-cde-presentations.php` y directorio `cde-presentations/`: validación, almacenamiento, comando de importación, revisión activa, política de acceso y modelo compartido.
- `site/web/app/themes/sage/app/Fields/Cde.php`: pestaña Presentación dentro de Lección CDE, con activación y repetidor de imágenes privadas; tipo ACF y subidas en el mu-plugin.
- `site/web/app/themes/sage/app/View/Composers/SingleCde.php`: exposición del modelo web solo después de comprobar acceso.

Operativa implementada: `wp cde presentation import PAQUETE --lesson-key=CLAVE --expected-revision=none|SHA`. Dry-run por defecto; escritura con `--apply` y activación con `--activate`. En el nuevo editor, una edición manual requiere además `--replace-editor-slides` para sustituirla. Ejecutar WP-CLI desde el host, en `site`, resolviendo el destino por entorno.

Pasos:

1. Preparar almacenamiento temporal privado; validar antes de instalar el directorio inmutable definitivo. Comprobar espacio libre, hashes y totalidad del paquete. Verificar por esquema, firma/MIME y lista de archivos que no se ha transferido ningún PDF: el servidor no recibe originales ni ejecuta la conversión. La validación del hash fuente procede del recibo local, mientras el servidor verifica todos los derivados recibidos.
2. Resolver un único post por clave, comprobar tipo y destino; registrar revisión candidata sin cambiar la activa. Guardar recibo de operación con revisión previa y nueva, clave y entorno.
3. Implementar bloqueo por lección y comprobación de revisión esperada. Activar cambiando un único puntero cuando todo esté íntegro; la primera activación no puede exponer paquetes incompletos.
4. Usar el repetidor ACF con imagen privada anidada. Guardar aplica las filas y su orden al manifiesto; probar añadir, sustituir, arrastrar, quitar, vaciar y recargar. Conservar las filas y la activación para rollback de una edición manual.
5. Repetir importación: cero duplicados y cero cambio de revisión activa. Simular fallo intermedio: revisión anterior intacta. Verificar rollback del puntero.

Salida: gestión administrativa y publicación de paquete completas en local, listas para staging.

## Fase 3. Entrega privada y contrato API

- Añadir configuración Nginx a través de la extensión de Trellis para el sitio, sin editar directamente plantillas de roles vendorizados. Crear raíz persistente privada y location `internal`; comprobar precedencia sobre reglas estáticas y que la ruta física no está bajo el document root.
- Añadir controlador web de GET/HEAD autenticado por cookie y entrega mediante `X-Accel-Redirect`. Verificar que Nginx conserva las cabeceras privadas y no aplica caché pública a las respuestas.
- Reutilizar exactamente la autorización de la lección con la política de su canal; no añadir permisos, membresías o listas de acceso propios del slider. La activación representa disponibilidad del contenido. Mantener la previsualización editorial separada. La falta de PMPro o de la dependencia móvil que hoy contiene la autorización no debe abrir acceso.
- Añadir endpoint de manifiesto y recursos binarios a `site/web/app/mu-plugins/cde-mobile/api.php`, delegando en el dominio compartido y `Sessions::user`. La entrega binaria necesita su recorrido explícito y no debe pasar como cadena/base64 por el serializador JSON genérico.
- Añadir esquemas nuevos en `cde-mobile/contract.json`; conservar intactos los contratos de `Lesson`/`Study` de apps actuales. Probar Bearer en staging mediante cliente HTTP, aunque la interfaz móvil se construya más adelante.
- Probar ausencia de filtración por REST genérico, ACF REST, medios, HTML público y URL directa. No basta un 403 global de la allowlist de staging: ejecutar los casos desde un origen admitido, variando únicamente sesión y permiso de lección.

Salida: imagen autorizada 200 y denegación reproducible de cada acceso no permitido, incluyendo copia de URL entre sesiones.

## Fase 4. Visor Swiper en Sage

Archivos propuestos:

- `resources/views/partials/lesson-presentation.blade.php`, incluido en `content-single-cde.blade.php` después del reproductor.
- `resources/js/lessons/presentation.js` y estilos propios con selectores acotados.
- Integración de inicio en `resources/js/app.js` y `resources/js/transitionScripts.js`; limpieza en `resources/js/barba.js`.

Implementar navegación manual, contador, zoom/restablecer, diálogo de vista ampliada, control de foco, movimiento reducido, `srcset`/`sizes` y carga acotada. Separar instancia y eventos del quiz. Conservar la proporción de cada página y no alterar el diseño del PDF.

Salida: flujo funcional de carga directa, cambio de lección, retorno con Barba, apertura/cierre, gesto táctil y sesión caducada. Pruebas visuales en ancho 360/390, 768 y 1440, más móvil horizontal. Comprobar teclado y lector de pantalla para controles; registrar aparte la limitación del contenido sin transcripción.

## Fase 5. Registrar las rutas en NocoDB

Cambio acotado en el inventario editorial compartido: reutilizar `presentacion_pdf_ruta`, ya creado por Aitor como `SingleLineText` y confirmado por lectura el 2026-10-01, para registrar las referencias a los PDF finales. No crear columnas de páginas, hashes, verificación o estado de staging/producción.

1. Preservar esquema y valores de las filas afectadas antes del cambio, conforme al procedimiento de NocoDB del proyecto.
2. Confirmar y reutilizar el campo `SingleLineText` existente; no recrearlo, cambiar su tipo ni modificar las demás columnas. Las futuras actualizaciones de valores usarán API/UI NocoDB, no SQL directo.
3. Preparar la correspondencia de las ocho claves con sus rutas finales relativas a `/Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos/Series CDE`; el valor empieza por la carpeta de serie, sin repetir `Series CDE/`. Comprobar que cada PDF ya existe, es legible y pertenece a la lección. Dropbox solo documenta la entrega; no escribir sus rutas ni destinos todavía pendientes de traslado. Si el campo ya existe y contiene un valor distinto, revisarlo antes de sustituirlo.
4. Registrar las rutas y releer las ocho filas para comprobarlas. Repetir la operación con las mismas referencias no debe producir cambios. Usar el soporte del campo desarrollado en `codex/cde-presentaciones-inventario` únicamente después de su integración e instalación coordinadas; no ejecutar escrituras desde la rama candidata.
5. Crear, si resulta útil, la vista «Presentaciones» con clave, serie, título y ruta. El TXT de Iván queda como evidencia del lote. La revisión publicada se consulta en WordPress; la conversión y sus comprobaciones, en el manifiesto e informe locales.

Salida: ocho presentaciones localizables desde su lección, sin metadatos técnicos duplicados en NocoDB.

### Reconciliación del inventario: desarrollada, instalación pendiente

El reconciliador se ha ampliado en `/Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos-worktrees/cde-presentaciones`, rama `codex/cde-presentaciones-inventario`: descubrimiento del PDF único de la subcarpeta directa `Presentación`, ruta relativa, preservación ante conflictos, relectura antes de aplicar e idempotencia. Tiene 21 pruebas específicas y supera la batería completa de 1.557 pruebas, más los 17 casos de capacidades. No se añade ninguna columna NocoDB. La [revisión inicial](../evidencias/presentaciones-2026-10-01/revision-inventario.md) conserva el diagnóstico previo; el [estado de la rama](../evidencias/presentaciones-2026-10-01/estado-rama.md) identifica la entrega actual.

La documentación de NocoDB de este hilo se preservó en el worktree y se retiró del checkout operativo mediante comprobación exacta de su contenido; los cambios ajenos se mantuvieron. No se recertificó el checkout compartido. Los cambios posteriores de motor de otros hilos requieren su propia gestión. Este plan, contrato y evidencias viven en el worktree web `/Users/aitor/.codex/worktrees/cde-presentaciones/espaciosutil.org`, rama `codex/cde-presentaciones-documentacion`.

Integración, versión de destino, certificación, catálogo e instalación se coordinarán después del aislamiento de ejecuciones. No reutilizar el certificado temporal anterior ni migrar manifiestos de lecciones activas implícitamente. El preflight de la rama candidata continúa rechazando cambios sin certificar; las pruebas en modo candidato no la habilitan para ejecución operativa.

## Fase 6. Despliegue y piloto en staging

1. Ejecutar validaciones locales proporcionadas al cambio: esquemas, importador, autorización, rutas, construcción del tema y regresión de API móvil afectada. No ejecutar una batería completa de pagos si no cambia esa capa.
2. Preparar backup del estado de staging afectado: opciones/metadatos, configuración y raíz privada. Registrar commit y release de destino. Confirmar que los pasos no apuntan a producción.
3. Desplegar por el flujo de staging vigente desde `main` una vez integrado el cambio. Validar Nginx antes de recarga, aislamiento y disponibilidad del sitio. No tomar documentos antiguos de despliegue como procedimiento vivo sin comprobar el workflow.
4. Importar y previsualizar Urantia; activar y ejecutar matriz funcional/de acceso. Importar la nueva versión de Un curso de milagros y repetir; cubrir el caso rasterizado mediante un fixture separado.
5. Importar Seth y Ramtha si el piloto pasa. Incorporar 05–08 solo después de resolver sus posts de staging mediante la fase 0; verificar otra vez sus claves y permisos.
6. Sustituir un paquete de prueba controlado, verificar rechazo de revisión anterior y rollback. No modificar contenido editorial real para fabricar la prueba.
7. Leer de vuelta la revisión activa y registrar el resultado en el informe de despliegue. NocoDB conserva únicamente la ruta del PDF, sin write-back de hashes ni estado de presentación. Guardar evidencias sin cookies, tokens ni contraseñas.

Salida: staging operativo y lote autorizado comprobado. Un build correcto por sí solo no significa que el visor esté validado.

## Fase 7. Integrar la automatización tras validar la infraestructura

Requisito de continuidad del proyecto, a implementar una vez superado el piloto. Las instrucciones de inventario se han actualizado únicamente en la rama candidata; las fases de conversión y publicación siguen sin añadirse. Repositorio de integración: `/Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos`.

1. Preparar un cambio de mantenimiento del contrato versionado de `cde-lesson-full-flow`, actualmente 2.1.2, con análisis de impacto sobre `skills`, `runtime`, `contracts`, `regressions` y `documentation`. Revisar `config/cde_lesson_flow.json`, `scripts/cde_lesson_flow.py`, publicación WordPress, reconciliador y allowlists; no actualizar solo el Markdown de la skill.
2. Añadir resolución de PDF final en `Series CDE` por clave al intake y congelar fuente/hash en el manifiesto. La recepción desde Dropbox y la comprobación de su ubicación final preceden al registro de la ruta en NocoDB y a la conversión. Modelar por separado ausencia concluyente, fuente requerida ausente, error de lectura y archivo válido. Una lección sin presentación opcional sigue su flujo normal con evidencia de ausencia; si estaba prevista y falla, WordPress no debe continuar omitiéndola como si estuviera completa.
3. Incorporar fase técnica local y verificación de presentación, con capacidad propuesta `presentations@1`, herramientas instaladas y evidencias exactas de cierre. Esta fase no escribe WordPress ni Bunny. Su dependencia inicial es la identidad verificada; no se encadena artificialmente a la traducción o al vídeo.
4. Hacer depender la fase WordPress del paquete de presentación validado cuando corresponda. El escritor WordPress importa las imágenes/metadatos y vincula la revisión en el borrador antes de `WP_VERIFIED`; para fecha futura, la transición `draft → future` sigue siendo la última mutación WordPress. No crear otro escritor concurrente sobre la lección.
5. Ampliar la reconciliación final y revisión independiente para comprobar el recurso completo y sus accesos, respetando el entorno y la continuación de publicación efectiva. Guardar la evidencia de presentación en el informe técnico; no añadir write-back de metadatos de presentación en NocoDB. No enviar comunicaciones ni crear `distribution_tasks` como efecto de incorporar una presentación.
6. Probar casos con PDF, sin PDF opcional, PDF requerido ausente, corrupto, duplicado, reemplazado durante ejecución, paquete idéntico, interrupción/reanudación, revisión concurrente, runtime incompatible, `draft`, `future` y aislamiento de entornos y conservación del único campo de ruta en NocoDB. Verificar también que el paquete remoto no contiene PDF.
7. Versionar y certificar mediante el procedimiento de mantenimiento existente después de las pruebas. Verificar catálogo y runtime que cargarán las skills; conservar las versiones de los flujos en curso y no migrarlos de forma implícita. La activación de esa capacidad en producción requiere que su infraestructura esté desplegada y autorizada allí; staging no habilita el destino `production` que hoy usa el flujo por defecto.

Salida: una futura lección incluye automáticamente su presentación cuando corresponde, sin carga manual en wp-admin y sin conversión en el servidor. El cierre de la infraestructura de staging debe señalar explícitamente si esta integración aún está pendiente; no presentar el flujo actual como compatible antes de implementarla.

## Matriz mínima de aceptación

| Área | Evidencia exigida |
| --- | --- |
| Inventario | Ocho rutas finales legibles en Series CDE vinculadas a ocho claves únicas; reutilización del campo SingleLineText existente; sin rutas Dropbox; comprobación posterior y repetición inocua; páginas y hashes solo en evidencia técnica |
| Identidad | Cero/múltiples candidatos detienen la importación; staging no reutiliza ni sobrescribe IDs/estado de producción |
| Conversión | Mismo número/orden de páginas; proporción preservada; variantes reales; revisión visual del piloto; sin extracción de texto ni OCR; sin campos de transcripción en paquete/API |
| Integridad | Archivo o hash incorrecto impide activación; error intermedio no rompe la revisión anterior; concurrencia detectada |
| Acceso | Miembro autorizado, anónimo, sesión caducada, miembro sin acceso a esa lección, lección inactiva/borrador y PMPro ausente; ensayo con lección pública si existe ese caso |
| Ficheros | Sin ruta pública directa, sin filtración por REST/ACF, HEAD/condicional autorizados, sin caché compartida; ningún PDF en el paquete ni en el almacenamiento remoto |
| Frontend | Flechas, gesto, contador, extremos, una página, zoom, diálogo, foco/Escape, movimiento reducido, error y reintento |
| Rendimiento | Red comprueba carga de actual/vecinas, no todas las páginas; variante grande solo al ampliar; registrar bytes del primer uso y tiempo de apertura |
| Integración | Acceso directo y navegación Barba repetida sin duplicar instancias; quiz, reproductor y progreso no afectados |
| Automatización futura | Conversión en origen, manifest/hash congelados, mismo escritor WordPress antes de programar, casos con/sin PDF y runtime incompatible, reconciliación final; skill actual intacta hasta la fase 7 |
| Apps | Manifiesto y binarios con Bearer correctos; esquema válido; rutas antiguas siguen respondiendo igual; no se declara validación física de Android/iOS |
| Reversión | Restaurar revisión previa y desactivar módulo conserva lección, vídeo y quiz; rollback de código compatible con metadatos nuevos |

No fijar un presupuesto de bytes ficticio antes de medir el piloto. El informe debe incluir dispositivo, red y dimensiones para interpretar latencia y nitidez. Las tres fuentes rasterizadas se sustituyeron, junto con el Círculo Carmesí, por las versiones nuevas de Dropbox; las ocho fuentes finales tienen texto y fuentes incrustadas. Para futuros PDF rasterizados, registrar si su resolución permite lectura suficiente; la ampliación no lo corrige automáticamente. [Sustituciones verificadas](../evidencias/presentaciones-2026-10-01/sustitucion-alta-resolucion.json).

## Reversión y mantenimiento

Desactivar presentación por lección o el interruptor de funcionalidad detiene su exposición sin borrar originales. Restaurar el puntero anterior revierte una sustitución de paquete; si hubo edición manual, restaurar también las filas ACF y la activación. Conservar el paquete anterior permite probarlo. Si falla la infraestructura privada, el backend debe denegar recursos, no recurrir a uploads públicos.

Un rollback de código deja los nuevos metadatos sin uso y preserva los archivos. En NocoDB, revertir únicamente las rutas escritas por la operación según snapshot y comprobación de que no haya cambios posteriores; el campo existente conserva su tipo y solo se restauran los valores anteriores afectados. Eliminar el campo o limpiar paquetes antiguos es una operación separada, nunca parte automática de un fallo.

Al terminar implementación, actualizar `docs/context/arquitectura-cde-y-frontend.md` con el estado real, enlazar evidencia de staging y mantener el contrato vigente. No marcar este plan como completado hasta disponer de los recibos.

## Fase posterior: apps y producción

Las apps añadirán pantalla nativa basada en el manifiesto, selección de variante por ancho y densidad, pinch-to-zoom, navegación, gestión de sesión y accesibilidad. Se decidirá entonces si conservar posición local por usuario/revisión; no asociarla a completar la lección sin contrato nuevo. Pruebas en APK reciente y dispositivos Android/iOS preceden a declarar integración terminada.

La promoción a producción requiere su propio destino verificado, backup, activación gradual e informe de despliegue; no añade campos de presentación a NocoDB. Se pueden reutilizar los paquetes por hash, pero los IDs WP, URLs, permisos y estado activo se resuelven en ese entorno. No está incluida en esta propuesta de staging.
