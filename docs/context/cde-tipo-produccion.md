# Modalidad de producción de las lecciones CDE

## Alcance y estado

Implementación de backend preparada el 3 de noviembre de 2026 por encargo de Aitor. **Código local probado; no desplegado en WordPress ni activado en Contenidos.** No se han editado contenidos, reconciliado lecciones ni escrito en NocoDB/WordPress/Bunny. La activación requiere el procedimiento de entrega de ambos proyectos; no basta con copiar el parche sobre una release sellada.

El 03/11/2026 Aitor aportó y autorizó el diseño de **cabecera** abierto en OpenPencil. La franja superior de metadatos está implementada localmente: símbolo familiar, serie/familia y modalidad. El índice recibió después autorización de rediseño por bandas familiares directamente en código local: ver [estructura y validación](arquitectura-cde-y-frontend.md#índice-del-curso-y-bloques). **El indicador de voz en las filas del índice sigue pendiente**; esa entrega cambia la estructura, no añade todavía la modalidad. Se usan directamente `tabler-robot-off` y `tabler-robot`, sin duplicarlos en el set personalizado. Los símbolos familiares existentes se conservan.

## Autoridad y valores

La autoridad es `cde_lecciones.tipo_produccion` en NocoDB. Es una **propiedad de la lección**, independiente de familia, serie, voz de las prácticas y formatos/archivos multimedia disponibles. No se deduce de esos datos ni de la ausencia de un aviso visible.

| Valor canónico | Etiqueta del editor |
| --- | --- |
| `video_grabado` | Voz humana |
| `audio_sintético` | Voz sintética |

`audio_sintético` lleva tilde; no se convierten silenciosamente variantes, etiquetas, mayúsculas o espacios. La API de NocoDB puede presentar la selección como texto o lista de una opción; el validador de Contenidos resuelve esa representación. **WordPress recibe y guarda un escalar exacto**, no una lista ni un booleano. No existe modalidad «no aplicable» en este contrato.

## Editor WordPress

En [el grupo ACF Lección CDE](../../site/web/app/themes/sage/app/Fields/Cde.php), pestaña **General**, después de «Lección activa» y antes de «Extracto Enriquecido»:

- Nombre: `tipo_produccion`.
- Clave estable: `field_cde_fields_tipo_produccion`.
- Radio horizontal con dos opciones, obligatorio y sin selección inicial.
- `default_value=''`, `allow_null=1`, `required=1`, sin opción libre «Otro» y `return_format=value`.

**Importante:** ACF preselecciona la primera opción si `allow_null=0`, aunque `default_value` esté vacío. `allow_null=1` permite representar el estado inicial sin marcar; **no autoriza guardar vacío**. El validador y el schema REST mantienen la obligatoriedad.

[El contrato de modalidad](../../site/web/app/themes/sage/app/Support/CdeProductionType.php) contiene opciones, validación exacta y schema REST. [El proveedor del tema](../../site/web/app/themes/sage/app/Providers/ThemeServiceProvider.php) registra esos filtros por la clave de este campo, sin modificar los demás radios/ACF:

- Conserva errores previos de otros validadores.
- Rechaza vacío, `null`, booleanos, números, arrays y variantes no canónicas.
- En entradas REST, el campo es escalar obligatorio, enum de dos valores, sin default ni `null` permitido. No se confunde la posibilidad de representar «sin dato» en el editor con permiso para borrarlo por API.

Las lecciones antiguas sin metadato **no se convierten a Voz humana**: siguen sin dato hasta su clasificación explícita. Al editarlas en el formulario ACF será necesario elegir la modalidad. No se ejecuta una migración masiva ni se usan datos de la BD local desactualizada.

Los filtros ACF no son una barrera universal frente a PHP/SQL directo. Las escrituras automáticas deben validar antes de aplicar y comprobar después lo persistido; no asumir que `update_field()` ejecuta la validación del formulario.

## Cabecera aprobada en OpenPencil

Referencia: documento abierto **Espacio Sutil**, página **Curso Desarrollo Espiritual**, frame «Rediseño series · Qué es El libro de Urantia · Vista pública 1585». Lectura del 03/11/2026; no se editó ni guardó el diseño. Los IDs de OpenPencil se remapean: localizar por nombre, no asumir IDs históricos.

- Franja bajo las migas de pan y antes del título, con separadores blancos verticales; texto Rubik de 12 px, iconos de modalidad de 24 px y símbolo familiar existente de 56 × 60 px (caja SVG).
- Dos líneas para serie y familia; la modalidad comparte fila en escritorio. En móvil los grupos se apilan sin cortar nombres ni desbordar.
- [Datos de cabecera](../../site/web/app/themes/sage/app/Support/CdeLessonMetadata.php): reutilizan la resolución familiar y el nombre de la serie raíz canónica. La modalidad procede exclusivamente de una única fila de metadato propio, válida y exacta. Ausencia, duplicado o valor inválido no se convierte en Voz humana ni se hereda del padre.
- [Parcial de metadatos](../../site/web/app/themes/sage/resources/views/partials/lesson-metadata.blade.php), [cabecera](../../site/web/app/themes/sage/resources/views/partials/post-header.blade.php) y [estilos](../../site/web/app/themes/sage/resources/css/layouts/lesson.css). Los textos de metadatos se escapan; los iconos son decorativos y se acompañan de texto visible.
- Sin modalidad válida se mantiene la serie/familia disponible sin inventar el indicador; sin ningún metadato no se renderizan franja ni separadores vacíos. No hay relleno de datos histórico por esta entrega.
- No se cambian navegación global, partículas, tokens de color familiar, acceso PMPro, botón de lección vista, contenido ni índice.

Pruebas adicionales, aisladas de WordPress/BD:

```bash
php site/tests/cde-lesson-metadata-unit.php
php site/tests/cde-lesson-header-render.php
```

Son 58 comprobaciones de datos y 129 de render Blade real con la fábrica de iconos instalada. El render exporta fixtures para comparar en navegador ambas modalidades, datos ausentes y nombres largos, **sin escribir datos simulados en lecciones reales**. Los fixtures no acreditan sincronización de datos productivos.

Validación visual local: 25 combinaciones de cinco casos a anchos de 320, 390, 640, 768 y 1600 px, sin desbordamiento horizontal; iconos de voz de 24 × 24 px. Capturas del render real con datos de prueba: [voz humana](../../site/tmp/cde-header-design/cabecera-humano-1600-final.png), [voz sintética](../../site/tmp/cde-header-design/cabecera-sintetico-1600-final.png) y [móvil](../../site/tmp/cde-header-design/cabecera-humano-390-final.png). La lección local real también cargó sin error; conserva ausencia de indicador donde todavía no tiene metadato de modalidad. Se restauró la página tras las pruebas y se cerró el espacio del navegador.

Al incorporar clases nuevas, regenerar el autoload del tema: la instalación local usa mapa de clases autoritativo. Ejecutar `composer dump-autoload --optimize --classmap-authoritative --no-scripts --no-plugins` desde el tema cuando se pruebe en local; el despliegue debe regenerar también su autoload mediante el proceso habitual. Compilar recursos con `npm run build`.

## Transporte desde Contenidos

El informe del 27 de octubre sobre `codex/cde-base-3.6.22` es histórico, no el runtime vigente. La comprobación actual del selector y binding instalado identifica **3.6.52** para flujos nuevos. Los manifiestos anteriores conservan su runtime fijado.

El parche se desarrolla **fuera de la raíz compartida y de las releases instaladas**, en un clon local independiente desde `b2bc94ce389e2e762cc1bc98f69eeb9f1bf9d066`, sin remoto, nueva versión, reserva, certificación o activación:

[Entrega y pruebas del transporte](</Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos-worktrees/cde-modalidad-backend-dev-20261103/docs/cde-modalidad-backend-dev.md>).

Contrato de integración:

1. Un plan nuevo con evidencia de modalidad válida incluye `acf_fields.tipo_produccion` y su acción de escritura antes del sellado.
2. El escalar coincide exactamente con la evidencia de la lección y su identidad. No se genera por defecto ni a partir de la familia.
3. En el primer guardado, usar la clave ACF estable, comprobando que la definición correspondiente está desplegada; no crear metadatos «dummy» por una búsqueda de nombre fallida.
4. Leer la modalidad persistida sin convertir tipos y compararla estrictamente cuando el plan la declara. Ausencia, valor distinto o tipo incorrecto impiden considerar conforme el readback.
5. La edición/eliminación del campo o acción en un plan sellado invalida los controles de integridad existentes. No rehacer hashes/HMAC para aprobar una discrepancia.
6. Los planes históricos sin ese atributo —incluidos los que ya tenían evidencia de producción— conservan su contrato: no se les añade el campo durante validación, recuperación o readback. Un atributo extra devuelto por la lectura moderna no altera sus hashes históricos.
7. No activar/reanudar planes antiguos ni rellenar publicaciones existentes por el mero hecho de instalar código nuevo.

El aviso que el publicador existente antepone al contenido de las lecciones sintéticas **no se modifica**:

> Esta lección utiliza una voz generada mediante síntesis de audio.

Decidir su convivencia con los futuros indicadores corresponde al diseño posterior; esta entrega no lo oculta, elimina ni duplica.

## Pruebas y activación

Prueba web aislada, con el builder y renderer reales instalados de ACF, sin WordPress/BD ni escrituras:

```bash
php site/tests/cde-production-type-unit.php
php site/tests/cde-families-frontend.php
```

[Pruebas de modalidad](../../site/tests/cde-production-type-unit.php): clave/campo/pestaña, enum, obligatoriedad, schema REST, errores previos y HTML de radios. Verifican que vacío y valor inválido no marcan ninguna opción, cada escalar válido marca sólo la suya y que desactivar `allow_null` sí produciría la selección implícita que se pretende evitar.

Resultados locales: **65 comprobaciones de modalidad WordPress correctas**, más las regresiones existentes de familias y sus 33 comprobaciones de frontend. La batería final de Contenidos pasó **266 tests**: payload, primer guardado ACF por clave (PHP local), definición ausente/incompatible, actualización, readback, discrepancias, alteraciones del plan, HMAC, reanudación y compatibilidad histórica. Consultar la entrega enlazada para comandos/resultados.

Antes de considerar esta función operativa: desplegar el backend web, comprobar la definición ACF y el editor en el destino, tramitar la candidata de Contenidos mediante reserva/certificación/revisión/instalación autorizadas, y verificar una escritura/lectura controlada. **No se ha hecho esa activación ni una prueba de escritura real en esta entrega.** Las pruebas aisladas no acreditan que las lecciones de producción ya tengan el dato.
