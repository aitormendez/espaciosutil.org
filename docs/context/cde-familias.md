# Familias de series: catálogo y asignación en WordPress

**Implementación local del 23/11/2026.** Hay una sola lista operativa: el repetidor ACF **Familias de series**, editable en Opciones. La definición del formulario pertenece al [MU-plugin del CDE](../../site/web/app/mu-plugins/espaciosutil-cde-families.php); no define las familias ni las sincroniza con una constante. El [modelo editorial](cde-familias-modelo-editorial.md) contiene las once familias acordadas, la correspondencia de las quince enseñanzas y el caso de la Guía. No se usa como tabla de ejecución.

## Cómo se usa

1. En el administrador abre **Opciones** y busca el grupo **Familias de series** en esa misma pantalla. Si el tema no registra «Opciones», el plugin proporciona una página propia. Una fila por familia: **código**, **nombre**, **icono** de la biblioteca de medios, **color de fondo** opcional y **color del texto** (blanco o negro). El fondo se expresa como `#RRGGBB`; vacío conserva el fondo CDE, sin inventar un color editorial. Las filas anteriores mantienen texto blanco por defecto. El código se guarda como texto (por ejemplo `01`); no es la posición de la fila, un ID de WordPress ni el número de una enseñanza. Guarda después de añadir, renombrar o reordenar filas. Puedes dejar el icono vacío mientras lo diseñas.
2. En **Lecciones del CDE → Series del CDE**, edita la serie raíz y selecciona una de las familias del repetidor. Una reclasificación requiere confirmación explícita; los bloques muestran la asignación heredada en modo lectura. Cada serie almacena sólo el código. Cambiar el nombre, el icono o el orden global no requiere editar las series.
3. Antes de eliminar una familia o cambiar su código, reasigna o desvincula las series que la usan. No se deben usar nombres o códigos de serie para adivinar la familia. La Guía de estudio no necesita una familia ficticia `00`; «esencial» y modalidad de voz no forman parte de este selector.

## Qué se conserva y qué desaparece

- **Desaparece la taxonomía** `familia_serie`: ya no se registra, no se consultan términos familiares y no hay un comando de semilla. La taxonomía **`serie_cde` sí permanece**, con serie raíz → bloques → subbloques. Las lecciones encuentran la serie por sus términos o por su cadena de padres. Contradicciones, metadatos duplicados y ciclos no se resuelven silenciosamente.
- El **campo de la serie raíz** se sigue llamando `familia_serie`. Es un valor del término de `serie_cde`, no una relación taxonómica ni una segunda lista. Los valores viejos no se borran ni se convierten al abrir la página. No se ejecuta SQL de limpieza: si una instalación llegó a crear términos de la taxonomía anterior, sus filas inactivas pueden permanecer en la base de datos, sin participar en el catálogo.
- **El catálogo comienza vacío en una instalación no configurada.** Ningún arranque, lectura REST o guardado corriente crea las once familias. Los slugs históricos se conservan para revisión. La continuidad visual del tema para datos históricos no autoriza usarlos en publicaciones nuevas ni identifica las nuevas familias por un icono viejo.
- El icono editable es **monocromo**: se usa la transparencia del adjunto como máscara en un SVG que toma `currentColor` del HTML. El archivo no necesita una versión blanca y otra negra. Usar símbolos con fondo transparente, no tarjetas con fondo opaco. El SVG de Medios no se inserta como código; los cuatro adjuntos provisionales son PNG y se conservan sin modificaciones.
- El repetidor se guarda en opciones globales de WordPress (`cde_familias_series`). Los iconos son adjuntos locales a cada instalación. Un catálogo se transporta por **código**, no por posición, ID de adjunto o ID de término en otro entorno. No se habilita por ello la subida de SVG ni se publica contenido privado.

## Consumo e integraciones

[Servicio PHP](../../site/web/app/mu-plugins/cde-families/families.php): `Families::catalog()` lee las filas en orden; `resolveSeries()` y `resolveLesson()` resuelven la asignación; `assignSeries()` valida contra el catálogo antes de escribir; `validatePublication()` compara el código esperado con el heredado. La propuesta de jerarquía sólo se usa antes de escribir: en la lectura posterior se pasan `[]` para verificar el estado realmente guardado.

`GET /wp-json/cde/v1/familias` permite leer el catálogo; el inventario autenticado `GET /wp-json/cde/v1/familias/inventario` y el campo `familia_resuelta` permiten ver qué series siguen con datos históricos o sin asignar. No usar el publicador para editar la lista o reclasificar series. Los contratos externos que todavía esperan los cuatro slugs **no son compatibles** con el nuevo catálogo: véase [la referencia del publicador](cde-familias-automatizaciones.md). No se han cambiado NocoDB ni planes históricos.

## Preparación de un futuro despliegue

Este cambio sólo prepara el entorno local. Antes de activar en otra instalación: inventariar códigos y consumidores, guardar copia de opciones/asignaciones, introducir y verificar el catálogo acordado, preparar la correspondencia **por serie** con aprobación editorial y revisar el publicador. No migrar por equivalencia entre cuatro familias antiguas y once nuevas. Conservar los símbolos anteriores de las series históricas hasta su revisión. Una reversión tras empezar a usar códigos nuevos exige restaurar también los datos correspondientes; no basta con volver al plugin anterior.

## Pruebas

- [Regresiones del backend sin BD](../../site/tests/cde-families-unit.php): catálogo, asignaciones, permisos, herencia y validación.
- [Comprobación de desarrollo en WordPress](../../site/tests/cde-families-live.php): sólo lectura; ejecutar desde `site` en el host con `wp eval-file tests/cde-families-live.php`.
- [Render del repetidor con ACF Pro real](../../site/tests/cde-families-acf-render.php): `wp eval-file tests/cde-families-acf-render.php` en desarrollo simula dos filas sólo en memoria y comprueba su orden, el control de icono y el botón de alta.
- [Pruebas del índice](../../site/tests/cde-course-family-render.php) y [cabecera](../../site/tests/cde-lesson-header-render.php): presentación de datos de familia sin modificar contenidos.
- [Diseño anterior de las cuatro familias](cde-familias-clasificacion-ivan.md) y [origen de sus símbolos](cde-familias-iconos.md): referencias históricas, no catálogo de opciones vigente.
