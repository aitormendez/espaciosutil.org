# Revisión de inventario y presentaciones

Fecha: 2026-10-01. Alcance: lectura de código y pruebas locales en el repositorio `Contenidos`, sin reconciliación contra servicios reales, sin cambios en NocoDB y sin modificar la skill, la policy, el runtime ni el certificado.

## Resultado

Crear `presentacion_pdf_ruta` y documentarlo no lo integra en la reconciliación. El campo se conserva porque queda fuera de los campos que el reconciliador puede escribir, pero no se descubre, rellena ni valida. Por tanto, una reconciliación correcta hoy no certifica el inventario de presentaciones.

| Hallazgo | Evidencia en Contenidos | Consecuencia |
| --- | --- | --- |
| El escáner no emite el campo y descubre lecciones mediante vídeo o carpeta de procesados | `scripts/cde_inventory_scan.py`: `find_lesson_dirs`, `build_row` | Una carpeta con solo el PDF no aparece en el escaneo |
| El campo queda fuera de las listas de hechos y escritura | `config/cde_publication_policy.yaml`, `scripts/cde_policy.py`, `scripts/cde_inventory_reconcile.py`: `_plan_for_key` | Se preserva, pero no se detectan rutas incorrectas ni diferencias |
| La clasificación de planificación no cuenta la ruta como evidencia técnica | `scripts/cde_inventory_reconcile.py`: `_planning_only_for_key` | Una fila con presentación registrada puede clasificarse como `planning_only` si se cumplen las demás condiciones |
| La documentación del inventario está incluida en el certificado del flujo | `config/cde_lesson_flow.release.json` | La actualización documental anterior provoca rechazo del preflight hasta tramitar su mantenimiento |

## Validación ejecutada

287 pruebas existentes superadas en 2,315 segundos. Cubren policy, esquemas, adaptador NocoDB, escáner, sincronización, verificación externa y reconciliación, incluida integración con NocoDB simulado. No equivalen a una prueba contra NocoDB, Bunny o WordPress reales. Registro: [salida de tests](revision-inventario-tests.log).

```sh
PYTHONDONTWRITEBYTECODE=1 .venv/bin/python -m unittest -q tests.test_cde_policy tests.test_cde_schemas tests.test_cde_nocodb_adapter tests.test_cde_inventory_scan tests.test_cde_inventory_sync_nocodb tests.test_cde_inventory_verify_external tests.test_cde_inventory_reconcile tests.test_cde_inventory_reconcile_integration
```

No se encontraron pruebas existentes del campo nuevo. Se ejecutaron seis comprobaciones adicionales temporales con fixtures y ejecutor en memoria: ruta vacía, ruta inexistente y ruta absoluta Dropbox no generan discrepancias; una actualización de otro hecho conserva la ruta; una carpeta de lección con solo un archivo de nombre PDF produce cero filas; una fila con ruta puede quedar como `planning_only`. El caso de escaneo comprueba el descubrimiento por nombre/estructura, no la validez del contenido de un PDF. Los casos de planificación no exigen evidencia externa real. Resultados: [casos específicos](revision-inventario-casos.json).

El comando `PYTHONDONTWRITEBYTECODE=1 .venv/bin/python scripts/cde_lesson_flow.py check` devuelve código 1 y `{"valid": false, "error": "unvalidated changes: docs/cde-lecciones-inventario-nocodb.md"}`. La causa es la sección documental añadida en la actualización anterior; las pruebas aprobadas no sustituyen ese control. No se ha alterado el certificado para ocultar la discrepancia.

## Ajustes propuestos, pendientes

1. Validar el campo registrado resolviendo la ruta relativa desde `Series CDE`: archivo existente, legible, PDF y perteneciente a la lección correcta. Rechazar rutas absolutas, Dropbox, escapes de la raíz y referencias a otras lecciones, incluidas rutas que escapen mediante enlaces simbólicos.
2. Incorporar el PDF de presentación al descubrimiento de lecciones y a la clasificación de planificación. Para rellenar automáticamente rutas vacías, definir primero una convención inequívoca de identificación: no elegir cualquier PDF de lecturas o materiales anexos. Los candidatos múltiples deben generar discrepancia.
3. Si el reconciliador debe escribir la ruta, ampliar de forma coherente el escáner, la policy YAML, las constantes/validaciones Python, los esquemas que corresponda y la lista de campos autorizados. Preservar una referencia existente ante errores de lectura o discrepancias; no borrarla ni sustituirla silenciosamente.
4. Añadir regresiones de ruta válida, vacía, inexistente, Dropbox, escape de raíz, lección incorrecta, PDF ambiguo, lección con solo presentación, preservación ante errores e idempotencia. Mantener una sola columna en NocoDB.
5. Tramitar el mantenimiento y la certificación del flujo mediante su procedimiento existente, incluida la actualización documental ya pendiente, y exigir que `cde_lesson_flow.py check` vuelva a pasar antes de usar el flujo certificado. No editar ahora la skill ni renovar solo hashes para eludir el control.

La validación del inventario puede prepararse independientemente del conversor y de WordPress. La incorporación automática de conversión, importación y publicación a `cde-lesson-full-flow` sigue aplazada hasta que esté validada la infraestructura.
