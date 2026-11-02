# Familias de series CDE: contrato de automatizaciones

Fecha: 2 de noviembre de 2026. Aitor confirma **cuatro familias**, una por grupo de Iván. La identidad visual queda a cargo de Aitor después de replicar las pantallas actuales en OpenPencil; este contrato no cambia el aspecto público.

## Catálogo y campo NocoDB

Campo propuesto en `cde_lecciones`: **`familia_serie`**, tipo **SingleSelect** (una sola opción), sin valor predeterminado. Aitor crea y mantiene el campo; la automatización no modifica su esquema ni sus valores editoriales.

| Valor estable (opción exacta) | Etiqueta WordPress | Códigos actuales, orientativos |
| --- | --- | --- |
| `espirituales-esenciales` | Enseñanzas espirituales esenciales | 01–08 |
| `otras-principales` | Otras enseñanzas del grupo principal | 09–29 |
| `otras-espirituales` | Otras enseñanzas espirituales | 30–69 |
| `informacion-complementaria` | Información complementaria | Desde 70 |

Las listas de enseñanzas permanecen abiertas. Los códigos explican el catálogo actual, pero **no sustituyen una asignación explícita**. Las familias 1 y 2 comparten el tramo general 01–29 sin fundirse en una familia única.

El índice actual también contiene **Guía de estudio**, que no figura en las listas familiares de Iván. No incorporarla por defecto a «esenciales» ni crear una quinta familia por inferencia. El backend admite series pendientes de clasificación y no bloquea el editor/publicador histórico por instalarse. Si una publicación nueva sujeta al contrato necesita una clasificación aún no definida, debe diagnosticarla para decisión editorial, no inventarla.

`tipo_produccion` sigue siendo la autoridad independiente de modalidad: `video_grabado` o `audio_sintético`. No se escribe ni deduce a partir de la familia. Una enseñanza esencial puede contener una lección con locución artificial sin cambiar de familia.

## Modelo WordPress

- `serie_cde` mantiene su jerarquía: raíz = enseñanza/serie, hijos = bloques.
- La taxonomía nueva `familia_serie` contiene el catálogo de familias.
- WordPress no vincula taxonomías directamente a términos. Por ello, la raíz de `serie_cde` guarda el slug familiar en el metadato `familia_serie`.
- La familia se asigna **una vez por serie**, nunca como una segunda clasificación editable por cada lección.
- Los bloques heredan la familia de su raíz. Las lecciones la resuelven por sus términos de serie o por la cadena `post_parent` hasta su índice de bloque. Una contradicción entre asignaciones propias y ancestros es un error, no una preferencia silenciosa.
- Los IDs WordPress son locales a cada instalación. La integración usa el slug familiar estable y lee los IDs del destino autenticado.
- El catálogo se inicializa de forma idempotente. No se migra contenido histórico, asignan series existentes ni despliega a producción por cargar el plugin.

La API PHP, las rutas de lectura y los comandos finales se describen en [el backend de familias](cde-familias.md). El publicador WP-CLI debe llamar explícitamente a `EspacioSutil\Cde\Families::validatePublication()` antes de cualquier escritura: el hook REST sólo se activa al enviar la familia y no protege las escrituras directas por PHP.

## Reconciliación de inventario

1. Leer esquema y datos autenticados; tratar `familia_serie` como **campo humano protegido**.
2. Normalizar únicamente la representación del selector; no convertir etiquetas libres, códigos o producción en una asignación.
3. Detectar campo ausente, valor vacío/desconocido, selección múltiple y familias contradictorias entre filas de la misma serie. No propagar valores a filas vacías sin encargo editorial explícito.
4. Comparar la familia NocoDB con la relación canónica de la serie WordPress cuando exista. Reportar por identidad completa de serie/lección, conservando paginación y detección de duplicados.
5. Incluir evidencia y diagnóstico por fila en dry-run. Una fila histórica sin familia no autoriza escritura ni debe impedir consultar el resto del inventario.
6. No añadir `familia_serie` a los campos de write-back ordinario. Los cambios técnicos no alteran modalidad, fechas, claves, títulos, rutas históricas ni jerarquías.

## Creación y publicación de lecciones

- Para nuevos flujos que apliquen este contrato, exigir un valor NocoDB válido y coherente con la serie; ausencia o conflicto bloquean antes de la mutación de esa lección.
- Fijar la familia y su evidencia en el plan sellado de publicación. Comparar con WordPress antes del staging y en readback; releer la fila y la relación de serie antes de la transición final para detectar cambios concurrentes.
- Si se crea una serie nueva, incluir su relación familiar **explícitamente** en el plan estructural autorizado. No crear términos o reasignar una serie existente durante la escritura ordinaria de una lección.
- Conservar dry-run, permisos/HMAC, detección de duplicados, PMPro, jerarquía y programación a las 19:00 Europe/Madrid. Esta integración no autoriza publicar inmediatamente ni cambia esos controles.
- Los flujos ya sellados conservan versión y contrato. No parchear releases inmutables, reanudar tareas, ni migrar publicaciones por esta implementación.

## Ejecución y activación

La implementación de Contenidos está encargada al CTO en [ESP-3179](https://mac-studio-de-aitor.tail1fa965.ts.net:8443/ESP/issues/ESP-3179), proyecto CDE - Lecciones. El encargo incluye candidata aislada, pruebas y revisión, sin activar el selector global ni escribir datos remotos. Consultar la tarea para conocer su resultado efectivo; la existencia de este contrato no acredita que el publicador instalado ya aplique familias.

**Entrega final de implementación, 02/11/2026: candidata aislada `3.6.46`, certificada, no instalada.** Incluye auditoría familiar del inventario sin write-back humano; familia sellada en alta estructural/publicación; preflight PHP antes de escritura, readback y relectura NocoDB antes de staging/programación. La revisión detectó que reutilizar la propuesta en readback podía ocultar un cambio real de familia: la versión final usa la propuesta sólo antes de escrituras estructurales y el estado persistido (`proposed=[]`) en readback, recuperación y programación, también en el guard PHP de la mutación.

El [informe final de Contenidos](</Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos/docs/esp-3179-familias-candidata-2026-11-02.md>) acredita **2349 tests correctos** en certificación y revisión independiente. DSH ejecutó de nuevo `check --checkout`: `valid=true`, versión `3.6.46`, certificado `e62cb4839e65741ad6f7dea8b8313dcd784caa58595a364a3a544d27a4c01fa5`; además repitió las 7 regresiones específicas de publicación familiar. Commit aislado de Contenidos: `4565f456b723f87a7cd5bd20c51111567cf42b8b`. Las candidatas anteriores no son la entrega final.

La lectura autenticada del esquema NocoDB realizada por el CTO devolvió HTTP 200 y ausencia del campo `familia_serie`. La tarea Paperclip queda bloqueada **sólo para la puesta en marcha**, hasta crear/verificar ese campo, desplegar el backend en destino, comprobar concesiones propias de los ejecutores y hacer una prueba controlada. El selector instalado sigue en `3.6.43`: no se activó la candidata ni se migraron tareas. No confundir implementación certificada con operación ya activada.

La [prueba de conformidad entre repositorios](../../site/tests/cde-families-cross-contract.py) contrastó el catálogo real WordPress local con esa candidata: **27 comprobaciones correctas**, sólo lectura y sin credenciales/red remota. Comprueba coincidencia de slugs/etiquetas, normalización estricta, rechazo de códigos/modalidades como familia, conflicto por serie y diagnóstico de Guía de estudio sin inventar clasificación. No sustituye la certificación integral ni un apply real.

El backend WordPress está implementado y probado en development. Se han inicializado los cuatro términos locales del catálogo, sin clasificar series o lecciones existentes. Su despliegue y la primera asignación real son operaciones posteriores explícitas; no hay sincronización de bases de datos.

## Criterios de aceptación

Pruebas sin credenciales/red: cuatro valores válidos; campo ausente y vacío; valor desconocido o múltiple; coherencia por serie; cambio de familia entre plan y apply; serie nueva con familia explícita; familia ausente en destino; bloques y lecciones anidadas; ciclos/conflictos; idempotencia; no modificación del campo humano; independencia de `tipo_produccion`; compatibilidad con planes anteriores.

Antes de activar nuevos flujos: campo creado y validado en NocoDB, backend desplegado en el destino, asignaciones de las series preparadas mediante un plan explícito, candidata de automatizaciones certificada y prueba controlada sin publicación real. No afirmar activación operativa sólo porque las pruebas aisladas pasen.
