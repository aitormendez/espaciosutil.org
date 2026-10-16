# Presentaciones: traslado y propuesta de instalación

Documento histórico. La instrucción posterior de Aitor sustituye la instalación propuesta por desarrollo en worktrees independientes. No instalar ni reutilizar el certificado temporal descrito aquí; consultar el [estado actual de las ramas](estado-rama.md).

Fecha: 2026-10-01. Traslado completado; instalación del runtime pendiente de autorización específica tras rechazo de la revisión automática de permisos.

## Traslado verificado

Ocho PDFs identificados por código contra Workflowy y las ocho filas únicas de NocoDB. Se revisaron todos los PDFs existentes en Series CDE, incluidos los excluidos de Git; ninguno correspondía al lote. Los ocho se copiaron a `<carpeta_local>/Presentación/<nombre original>.pdf`, se verificó el SHA-256 de todo el lote y solo entonces se retiraron los originales de Dropbox. Se conservaron las carpetas de lección existentes registradas en NocoDB; las diferencias menores de acentos, comillas y puntuación en Workflowy no motivaron renombrados. Evidencia: [traslado](traslado-verificado.json).

## Cambio preparado y alcance

[Parche revisable](inventario-propuesta.patch), con [huellas previas y candidatas](inventario-propuesta-manifest.json). Copia de validación: `/private/tmp/cde-presentations-candidate`.

- El escáner reconoce un PDF único en la subcarpeta directa `Presentación`, incluida una lección sin vídeo; comprueba firma, lectura y archivo regular y rechaza enlaces y ambigüedad.
- El reconciliador puede rellenar únicamente una referencia vacía. Una ruta registrada diferente, inexistente o ajena bloquea y se conserva. Antes de escribir vuelve a comprobar la presentación detectada. Un valor vacío opcional no genera escrituras masivas de cadenas vacías.
- Se añade `presentacion_pdf_ruta` exclusivamente a `atomic_fact_contract.filesystem_fields` y a su validación Python. Las allowlists de las fases del flujo y la desactivación de escrituras desatendidas permanecen intactas.
- La policy tiene una huella autenticada por el runtime; incorporar el campo exige actualizar esa huella al contenido revisado y elevar el contrato de 2.1.2 a 2.1.3. El certificado se regenera con el comando de mantenimiento que ejecuta las pruebas; no se edita a mano para ocultar cambios.
- La skill y las fases del flujo quedan intactas. Conversión, importación, slider, despliegue y migración de flujos en curso quedan fuera de esta instalación. Un flujo antiguo que detecte incompatibilidad requiere revisión expresa; no se migra implícitamente.

## Pruebas y lectura real

La certificación de la copia temporal completó 1550 pruebas y sus casos de capacidades obligatorios. Hay 14 pruebas nuevas específicas de presentaciones. Las primeras ejecuciones completas fallaron porque la copia aislada carecía del contexto Git y de documentos auxiliares usados por las pruebas; se completó el entorno y se repitió la batería sin modificar esos controles. [Registro final](inventario-candidato-tests.log).

El [dry-run contra las ocho lecciones reales](inventario-candidato-dry-run.json) pasó todas las precondiciones, sin discrepancias. Propone ocho actualizaciones: la ruta y la fecha de revisión del inventario; en Kryon y Conversaciones con Dios también refresca `bunny_last_verified_at` tras su comprobación real. No propone alterar IDs, títulos, fechas editoriales ni estado de publicación. No se ha aplicado ninguna escritura remota.

## Instalación pendiente

Tras autorización específica: comprobar que no cambiaron los archivos respecto al manifiesto, instalar el parche, ejecutar la certificación en Contenidos y verificar el preflight. Volver a generar el dry-run y aplicar el reconciliador canónico con canary de una fila, readback, resto del lote y segunda reconciliación sin cambios. Actualizar el plan y el estado documental con los resultados reales.

La revisión automática rechazó el intento inicial porque consideró insuficientemente específica la autorización para modificar código, policy, versión y hash de confianza mediante sustituciones. La propuesta aislada no cambia esa frontera en el runtime instalado. El único cambio previo pendiente allí sigue siendo la documentación del inventario; por eso su preflight continúa pendiente hasta instalar y certificar esta propuesta.
