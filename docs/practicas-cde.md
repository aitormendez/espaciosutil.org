# Prácticas del CDE: objetivo y decisiones de diseño

> Referencia vigente (2026-10-16): [Prácticas CDE: implementación, edición, visor, voz y operación local](context/practicas-cde.md). Este documento conserva decisiones, propuestas y evidencias fechadas; las ubicaciones de prueba y estados de iteraciones anteriores no deben interpretarse como el estado actual.

Fecha de inicio: 2026-09-30. Última actualización: 2026-10-14.

Estado: decisiones de diseño e inventario. El catálogo y el visor de escritorio con narración están implementados en la rama aislada y se prueban mediante una copia local en la VM. La integración operativa sigue pendiente. Consultar [alcance implementado, pruebas y pendientes](practicas-cde-implementacion.md); las propuestas de inventario remoto de este documento siguen pendientes de integración con NocoDB.

## Objetivo

Incorporar al Curso de Desarrollo Espiritual una sección de prácticas que reúna las instrucciones presentes en los textos revelados que se enseñan en el curso, como meditaciones y otros procedimientos. El alumno debe poder consultarlas tanto desde un catálogo general como desde las lecciones donde se mencionan.

Cada práctica tendrá una ficha propia y reutilizable. La ficha mostrará también las lecciones que tienen asignada esa práctica, para recuperar el contexto donde se ha revelado y explicado.

## Decisiones acordadas

### Acceso por membresía

Cualquier membresía activa da acceso a todo el contenido del CDE, incluidas las lecciones, la sección de prácticas y sus fichas. No se establecerán diferencias de acceso a las prácticas según el nivel de membresía ni según las lecciones a las que estén asociadas.

La relación entre prácticas y lecciones expresa contexto editorial, no permisos. La implementación debe aplicar el requisito de membresía activa también a las vías de acceso al contenido que utilicen la web y, posteriormente, las aplicaciones.

Esta decisión sustituye la propuesta inicial de estudiar reglas particulares de acceso para prácticas compartidas entre lecciones.

### Práctica como contenido independiente

La base de diseño es un Custom Post Type (CPT) de prácticas, separado del CPT de lecciones `cde`. Se acuerda el nombre técnico `practica` para el CPT y `cde_practicas` para la tabla de NocoDB. Las rutas públicas se decidirán al concretar la implementación.

El contenido de una práctica se mantiene en su ficha y se reutiliza al mostrarlo en las lecciones; no se crean copias independientes de sus instrucciones en cada lección.

### Denominación acordada

Se utilizará «Prácticas» como nombre de la sección y «Práctica» para cada ficha. El bloque dentro de una lección se denominará «Prácticas de esta lección».

El término abarca meditaciones, ejercicios concretos y procedimientos de atención o aplicación cotidiana, incluidos los que no tienen una secuencia o un final definidos. Los materiales de Iván pueden conservar la denominación «Ejercicio práctico», sus títulos y códigos como `EP-0.1`; no se crearán dos tipos de contenido ni dos inventarios por esa diferencia de vocabulario.

### Asignación desde las lecciones y consulta inversa

Las prácticas se asociarán a las lecciones en WordPress mediante un campo ACF de tipo `Relationship`, elegido el 2026-10-01. Una lección podrá tener varias prácticas y una práctica podrá estar asociada a varias lecciones.

La ficha de la práctica incluirá una búsqueda inversa que identifique qué lecciones tienen esa práctica asignada y permita acceder a ellas. Esta lista se obtiene de las asignaciones realizadas en las lecciones y no se mantiene manualmente en la práctica.

La consulta inversa aporta contexto: permite conocer las lecciones donde se ha mencionado, revelado o explicado la práctica. Los enlaces visibles para el alumno deberán corresponder a lecciones disponibles para su consulta.

El tipo de campo ACF está decidido: `Relationship`. Está pendiente el orden de presentación de las lecciones en la ficha. La propuesta de inventario descrita más abajo plantea mantener las asociaciones editoriales en NocoDB y trasladarlas a este campo de WordPress; ese reparto de responsabilidades sigue pendiente de aprobación.

### Texto como formato inicial

La primera versión tratará las prácticas como instrucciones de texto. El modelo debe permitir añadir posteriormente otros modos de presentación cuando sean adecuados para una práctica concreta.

Se analizará cada práctica para determinar si admite una guía narrada con voz sintética, una presentación por pasos con un botón «Siguiente paso», ambos formatos o únicamente texto. La conversión depende de su estructura y no se presupone que todas las prácticas puedan adaptarse de la misma manera.

### Presentación interactiva: acuerdo del 2026-10-12

En la conversación aportada por Aitor, Iván respondió «Vale a todo» a la propuesta de presentación por pantallas y a colocar la ejecución al final, después del contexto. Es un acuerdo editorial sobre esta adaptación; no implica que la interfaz interactiva o la narración estén implementadas.

La práctica se plantea como culminación del aprendizaje: el contexto prepara al alumno para realizarla. En la versión Markdown de EP-0.1, cada idea sin numerar y sus explicaciones subordinadas forman un párrafo, con punto y seguido; las ideas hermanas permanecen en párrafos separados. Los niveles numerados se representan mediante encabezados.

El orden de lectura acordado para EP-0.1 es: procedencia, objetivo, utilidad, ritmo y progresión, avisos de la fuente y, por último, ejecución paso a paso. La renumeración de la adaptación es `1 → 1`, `2 → 2`, `3 → 3`, `5 → 4`, `6 → 5` y `4 → 6`; se aplica también a los subapartados. No se modifica la numeración ni el orden del TXT fuente.

La presentación interactiva tendrá una pantalla de cabecera al inicio de cada bloque de párrafos, con toda la ruta de epígrafes: padre, hijo y los niveles adicionales que existan. Tras el ajuste confirmado el 2026-10-14, cada padre dispone de su propia portada. La pantalla del hijo conserva la ruta completa visualmente y sólo narra el epígrafe nuevo, con punto final para el motor de voz. Después, cada párrafo ocupa su propia pantalla, con avance manual mediante «Siguiente». La narración acordada se activa mediante «Activar audio» y reproduce la pantalla actual y las siguientes mientras siga activa; no avanza automáticamente. Se reutiliza la voz local entrenada de Iván. La implementación de escritorio está descrita en el informe enlazado; móvil y Android/iOS siguen pendientes. La navegación manual permite permanecer en cada paso el tiempo necesario.

La versión de lectura contiene 6 encabezados de apartado, 15 de subapartado y 28 párrafos de contenido. Con las portadas de bloque confirmadas el 2026-10-14, se generan 6 portadas, 15 cabeceras de subapartado y 28 pantallas de párrafo: 49 pantallas. Los padres se siguen mostrando en las cabeceras de sus hijos, pero no se vuelven a narrar. Este criterio sustituye la primera implementación de 43 pantallas. La etiqueta de bloque «00. Presentación» es contexto del documento, no una instrucción de práctica.

### Web primero; aplicaciones después

La funcionalidad se implementará primero en la web del CDE. Después se incorporará a las aplicaciones nativas Android e iOS.

La fase web debe establecer el contenido y sus relaciones de forma reutilizable para la posterior integración móvil. La primera implementación no incluye desarrollar las pantallas de las aplicaciones.

## Estructura de la ficha: propuesta pendiente de acordar

La siguiente tabla es una base para discutir los datos de la ficha, no un esquema de campos aprobado. Antes de implementarla hay que determinar qué información existe en las prácticas reales, qué campos son obligatorios y cómo se editan.

| Dato propuesto | Propósito | Estado |
| --- | --- | --- |
| Título | Identificar la práctica en el catálogo, la ficha y las lecciones. | Propuesto; concretar criterio de denominación. |
| Descripción breve | Explicar de forma concisa en qué consiste. | Propuesto; decidir si es necesaria. |
| Instrucciones de texto | Presentar el contenido inicial de la práctica. | Formato inicial acordado; estructura editorial pendiente. |
| Referencia al texto de origen | No disponible. | Descartada en esta fase; el contexto visible son las lecciones relacionadas. |
| Contexto o notas editoriales | Aclarar información necesaria para comprender las instrucciones. | Propuesto; decidir si requiere un campo separado. |
| Lecciones relacionadas | Recuperar el contexto donde se enseña la práctica. | Acordado; dato derivado por consulta inversa, no campo manual. |
| Clasificación | Posible exploración futura. | Aplazada expresamente; no se implementa ahora. |
| Adaptaciones por pasos o audio | Ofrecer modos guiados cuando la estructura de la práctica lo permita. | Visor web y audio preparado implementados; revisión editorial y auditiva por práctica. |

Se propone distinguir el texto original, las explicaciones editoriales y las adaptaciones guiadas para conservar la procedencia de las instrucciones. Esta separación y su traducción a campos concretos deben acordarse al analizar ejemplos reales.

Las lecciones relacionadas son el contexto disponible para el alumno. No se añade ni muestra una referencia al texto de origen. El código editorial se conserva como dato interno, sin atribuir una única serie a la práctica.

## Datos de la relación: decisión pendiente

Una asociación simple permite indicar que una práctica aparece en una lección. Si además se necesita registrar el minuto de la explicación, el apartado del texto o una nota específica de esa aparición, esos datos pertenecen a la relación entre la lección y la práctica.

No se ha acordado incluir esa información adicional. Se conserva la decisión de usar `Relationship` en WordPress. Si más adelante cada asociación necesita evidencias, ubicaciones o notas propias, se evaluará una tabla de asociaciones en el inventario y su representación complementaria en WordPress, sin sustituir el campo elegido ni mantener dos listas editoriales independientes.

## Comportamiento esperado en la web

- La sección de prácticas permite revisar el conjunto de prácticas publicadas.
- Cada práctica dispone de una ficha con sus instrucciones y las lecciones que la tienen asignada.
- Cada lección muestra las prácticas que tiene asignadas, reutilizando el contenido de sus fichas.
- Al cambiar las asignaciones de una lección, la consulta inversa refleja el cambio sin editar manualmente la ficha de la práctica.
- Una membresía activa permite acceder a las lecciones y a las prácticas, sin distinciones por nivel de membresía.

El catálogo muestra un único enlace con el título de cada lección relacionada y un acceso al visor individual. La ficha y el incrustado usan el mismo visor. El filtro por serie consulta las series de las lecciones asociadas; no clasifica la práctica.

## Próximas decisiones

1. Analizar una pequeña selección de prácticas reales con estructuras distintas para concretar los datos de la ficha.
2. Acordar la separación entre instrucciones originales, referencias y explicaciones editoriales, así como los campos obligatorios y opcionales.
3. Acordar la propuesta de inventario en NocoDB, la responsabilidad sobre las asociaciones y si cada aparición necesita ubicación y contexto propios.
4. Definir la consulta inversa del campo `Relationship`, incluido el orden de las lecciones relacionadas.
5. Concretar la presentación del catálogo, la ficha y el bloque de prácticas en las lecciones para la primera versión web.
6. Tras validar la versión web, definir la incorporación del contenido y las relaciones a la API y a las aplicaciones Android e iOS.

La segmentación por titulares y párrafos está acordada para EP-0.1; debe evaluarse su encaje en las demás prácticas. La narración se prueba en escritorio; la incorporación móvil sigue pendiente.


## Inventario editorial: propuesta del 2026-10-01

Estado: propuesta para discusión. Se han leído archivos locales y documentación del inventario existente; no se ha consultado el esquema ni los registros vivos de NocoDB, ni se han creado tablas, relaciones o automatizaciones.

### Evidencia disponible

La comunicación de Iván del 2026-09-30 enumera un ejercicio depositado: `EP-0.1. Sentir el suelo: una capa de energía cada vez`, del proyecto `08 - El Círculo Carmesí`. Lo describe como texto base aprobado y pendiente de adaptación digital. Ese estado procede del comunicado, no de una nueva revisión editorial del contenido.

El TXT del ejercicio se ha leído. Contiene seis apartados: procedencia, objetivo, utilidad en el camino del alumno, instrucciones paso a paso, ritmo y progresión, y avisos de la fuente. Es un texto editorial preparado para el curso, con explicaciones y citas; no debe etiquetarse automáticamente todo su contenido como transcripción literal de la fuente revelada.

En la revisión inicial, ni el comunicado del 2026-09-30 ni el TXT identificaban las claves exactas de las lecciones relacionadas. Esa falta de procedencia editorial queda resuelta por la entrega del 2026-10-08 descrita a continuación; la correspondencia técnica en NocoDB sigue pendiente. El código `EP-0.1` no demuestra que corresponda a una lección `0.1`.

El comunicado inventaría ejercicios depositados; no prueba que sean todas las prácticas mencionadas hasta ahora en el curso. La revisión de cobertura debe contemplar también prácticas detectadas en lecciones para las que todavía no se haya entregado una ficha.

La documentación local de `cde_lecciones` establece `clave_leccion` como identidad canónica y separa los campos editoriales de los hechos técnicos verificados. El reconciliador existente tiene una lista explícita de campos permitidos y no crea filas automáticamente. No debe suponerse que ese proceso gestione ya las futuras prácticas.

### Procedencia confirmada y organización de entregas — 2026-10-12

La comunicación de Iván aportada en este hilo establece que cada ejercicio se conserva una sola vez en la carpeta de la pieza de procedencia directa, sea un tema ordinario, una profundización o una reprofundización. Cada proyecto mantiene en su raíz un TXT con su inventario y ubicaciones. Las carpetas generales «Ejercicios prácticos» y el listado conjunto de la raíz AITOR CDE dejan de ser las ubicaciones de referencia. No mover ni duplicar las fuentes al inventariarlas.

El inventario de El Círculo Carmesí del 2026-10-08 confirma expresamente que `EP-0.1 — Sentir el suelo: una capa de energía cada vez` procede de `0.4.3.2 — La canalización como permiso y el ejercicio de sentir`. Su nueva ubicación se ha comprobado el 2026-10-12. La huella SHA-256 del TXT coincide con la declarada en ese inventario: `ccf46e9ab58a816b5673bc0edfbc19e8fa6ae66b872b7d654ab9c6764cb053d8`. Esto verifica identidad de bytes con la entrega descrita, no una nueva aprobación editorial.

Ruta del TXT relativa a AITOR CDE: `08 - El Círculo Carmesí/00 - Presentación/0.4. - Geoffrey Hoppe y la naturaleza de la canalizacion/0.4.3.2/EP-0.1. - Sentir el suelo una capa de energia cada vez.txt`.

La procedencia editorial ya está confirmada; queda localizar y verificar la fila y la `clave_leccion` exactas en NocoDB antes de generar la asociación técnica. No deducir esa clave del código EP ni de la numeración de la carpeta. La pieza de origen no impide que otras lecciones también mencionen la misma práctica: esas apariciones se registrarán por separado en la relación muchos a muchos, sin copiar el TXT.

### Responsabilidades propuestas

| Sistema | Responsabilidad propuesta |
| --- | --- |
| Archivos entregados por Iván | Conservar el texto base y su procedencia; la comunicación sirve como aviso e inventario de entregas. |
| NocoDB | Mantener la identidad de cada práctica, localizar su fuente, registrar la revisión editorial y establecer las asociaciones confirmadas con `cde_lecciones`. |
| WordPress | Publicar las fichas y mantener una representación de las asociaciones en ACF `Relationship`, desde la que se resuelve la consulta inversa. |
| Aplicaciones Android/iOS | Consumir posteriormente el contenido publicado y las relaciones a través de la API del CDE. |

El texto base seguirá en archivos; no se propone copiarlo manualmente a otra columna de texto completo en NocoDB. WordPress almacenará la versión digital publicada. La titularidad de las futuras adaptaciones y el tratamiento de cambios editoriales en WordPress deben concretarse antes de automatizar su actualización.

### Tabla propuesta: `cde_practicas`

Se recomienda una tabla independiente de prácticas vinculada a la tabla existente `cde_lecciones`. Su nombre acordado es `cde_practicas`; la estructura detallada y el flujo de gestión del inventario siguen en propuesta. Los materiales denominados «Ejercicio práctico» se incorporarán a este mismo inventario.

| Campo o grupo propuesto | Uso |
| --- | --- |
| `clave_practica` | Identidad estable y única, compartida con WordPress. Su formato está pendiente; no dependerá del título ni de la ruta del archivo. |
| `codigo_practica`, `titulo` y serie de origen | Conservar la denominación editorial, incluido `EP-0.1`, y su contexto. No se presume que el código humano sea único entre series. |
| `ruta_fuente` | Referencia al TXT, preferiblemente relativa a una raíz documental acordada. Puede estar vacía para una práctica detectada pero todavía no entregada. |
| `sha256_fuente` y fecha de comprobación | Identificar la versión del TXT realmente leída y detectar modificaciones; un hash no prueba aprobación editorial. |
| Revisión editorial | Registrar por separado la aprobación del texto y la revisión de sus asociaciones, con fecha y responsable; vocabulario pendiente. |
| `lecciones` | Relación múltiple con filas existentes de `cde_lecciones`, solo para asociaciones confirmadas. |
| Referencia al post de la práctica en WordPress | ID, URL, estado observado y fecha de verificación del post del CPT de la propia práctica, en el entorno correspondiente. Inclusión acordada; nombres definitivos de los campos pendientes. |

La tabla de prácticas no duplicará los IDs ni las URLs de los posts de las lecciones: las relaciones apuntan a las filas existentes de `cde_lecciones`, donde ya consta esa correspondencia. `clave_leccion` identifica cada lección; `clave_practica` identifica cada práctica y permite localizar su propio post del CPT en WordPress.

Se acuerda incluir la referencia al post del CPT de la propia práctica. Su ID, URL, estado observado y fecha de verificación son datos técnicos comprobados en WordPress; la existencia de un post no implica que esté publicado. `clave_practica` sigue siendo la identidad estable compartida entre sistemas. La correspondencia se resolverá por entorno; los IDs numéricos no deben suponerse iguales en local, staging y producción.

Para empezar se propone una relación muchos a muchos nativa entre `cde_practicas` y `cde_lecciones`, sin una tercera tabla editorial mantenida manualmente. Si se necesita registrar evidencia, minuto, pasaje, estado de revisión u orden específico por asociación, se evaluará una tabla explícita `cde_practica_leccion`. No se mantendrán ambas representaciones como fuentes independientes.

### Cobertura y revisión del inventario

Un enlace vacío no permite distinguir entre «revisado, sin prácticas» y «todavía no revisado». Se propone registrar en las lecciones la revisión de prácticas y su fecha, vinculándola a la versión del texto revisado cuando esté disponible. Así se podrán obtener vistas de lecciones pendientes de revisar, prácticas sin correspondencia confirmada y fuentes que han cambiado desde la última revisión.

Una búsqueda de texto o un análisis asistido podrá sugerir correspondencias, pero los enlaces solo se incorporarán tras confirmar el pasaje y la identidad exacta de la lección. Una entrega nueva no se enlazará automáticamente por coincidencia de título, código o carpeta.

### Flujo propuesto

1. Consultar el inventario TXT de la raíz de cada proyecto y localizar cada entrega en la carpeta de su pieza de procedencia; también detectar prácticas mencionadas en lecciones todavía sin ficha. Comprobar si ya existe en el inventario antes de darla de alta.
2. Registrar su identidad y, cuando exista, localizar y comprobar el TXT entregado, manteniendo separado el estado editorial de los hechos técnicos.
3. Confirmar con Iván o con pasajes concretos qué filas de `cde_lecciones` deben asociarse. Conservar como pendientes las correspondencias no resueltas.
4. Preparar la ficha de WordPress y trasladar las asociaciones confirmadas al campo `Relationship`, resolviendo ambas entidades por sus claves estables.
5. Verificar que las lecciones muestran las prácticas y que la consulta inversa de cada práctica devuelve las lecciones esperadas.
6. Comprobar el resultado de la transferencia y registrar en el inventario los datos técnicos verificados del post de la propia práctica: ID, URL, estado observado y fecha de verificación. Los datos de los posts de las lecciones permanecen únicamente en `cde_lecciones`. La publicación seguirá siendo una acción editorial explícita; registrar o sincronizar una práctica no autoriza publicarla.

La primera transferencia puede ejecutarse de forma supervisada, sin introducir un proceso periódico. La futura sincronización de asociaciones tendrá una dirección editorial NocoDB → WordPress. Las modificaciones directas del `Relationship` deberán detectarse como discrepancias; no se sobrescribirán silenciosamente ni se convertirán en una segunda lista maestra.

Una relación con una lección aún no publicada puede existir en el inventario; su visibilidad en la web depende de que ambos contenidos estén disponibles para el alumno. Si falta la correspondencia de un post o la identidad es ambigua, se informa y se pospone esa transferencia, sin eliminar asociaciones por deducción. Un cambio o ausencia de archivo tampoco autoriza borrar una práctica.

Antes de implementar se verificará el esquema vivo de NocoDB y se preparará el cambio con el procedimiento de snapshot documentado en Infraestructura VPS. El inventario de prácticas necesitará un contrato propio o una ampliación explícita y validada; no se reutilizará sin cambios la reconciliación de lecciones.

### Fuentes consultadas

- Comunicación vigente de Iván: `/Users/aitor/Library/CloudStorage/Dropbox/AITOR CDE/08 - El Círculo Carmesí/Comunicación a Aitor - Ejercicios prácticos depositados - 20261008.txt`. El comunicado general del 2026-09-30 fue consultado inicialmente y ya no está en su ubicación anterior.
- Ejercicio comprobado el 2026-10-12: `08 - El Círculo Carmesí/00 - Presentación/0.4. - Geoffrey Hoppe y la naturaleza de la canalizacion/0.4.3.2/EP-0.1. - Sentir el suelo una capa de energia cada vez.txt`, relativo a la raíz Dropbox AITOR CDE. Sustituye a la antigua ubicación en «Ejercicios prácticos».
- Entrada de infraestructura: `/Volumes/E/Infraestructura VPS/AGENTS.md` y `docs/nocodb.md` de ese repositorio.
- Contrato local del inventario: `/Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos/docs/cde-lecciones-inventario-nocodb.md`.
- Documentación oficial de [relaciones de NocoDB](https://nocodb.com/docs/product/tables/fields/field-types/links-based/links). La disponibilidad y configuración en la instancia instalada están pendientes de verificación.

## Controles de escritorio acordados

El visor usa modo oscuro, «Activar audio» con iconos Tabler `volume`/`volume-off`, navegación `arrow-badge-left`/`arrow-badge-right` filled y pantalla completa. Al entrar en pantalla completa se oculta el índice; «Mostrar índice»/«Ocultar índice» permite recuperarlo. El cambio de tamaño conserva pantalla y reproducción. Cada padre tiene una portada independiente. La narración de la pantalla siguiente omite el padre y dice sólo el epígrafe nuevo. El índice está disponible también en «Texto completo» y enlaza con los encabezados del texto.

Implementación, instalación de voz y prueba local: [visor de escritorio y narración](practicas-cde-visor-y-voz.md). No se ha adaptado móvil en esta fase.
