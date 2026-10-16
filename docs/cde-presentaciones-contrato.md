# Presentaciones CDE: contrato v1

Fecha inicial: 2026-10-01. Actualización: 2026-10-15. El visor privado está desplegado en staging; el editor ACF con diapositivas editables está implementado y validado solo en local. Conversión de PDF en origen, conservación local del PDF y autorización compartida con la lección. Apps e integración del flujo automatizado pendientes. [Detalle del editor y validación](cde-presentaciones-editor.md).

## 1. Decisión recomendada

Mantener el inventario de presentaciones en la fila existente de `cde_lecciones`, identificada por `clave_leccion`. Recibir únicamente el PDF definitivo de Iván. Convertirlo en el Mac en un paquete de imágenes y metadatos reproducible, importar únicamente ese paquete en WordPress y mostrarlo con Swiper. El PDF se conserva en la biblioteca final `Series CDE` y en su archivo local de revisión por hash; no se sube ni almacena en el servidor web. WordPress conserva la revisión activa de cada lección y autoriza su consulta; NocoDB no participa en las peticiones de los alumnos.

La pestaña ACF «Presentación», dentro de «Lección CDE», permite activar y editar un repetidor con imagen privada anidada. El paquete importado proporciona las páginas iniciales; las filas guardadas determinan el contenido y orden publicado, permitiendo añadir, sustituir, reordenar y quitar imágenes. La importación posterior no sobrescribe una edición manual sin una opción explícita.

El importador y el contrato de lectura deben vivir en un mu-plugin independiente de Sage. ACF aporta la interfaz administrativa; Sage, la presentación web; la API móvil, un adaptador de autenticación y serialización. La lectura de una presentación no dependerá de `get_field()` ni de que el tema esté activo.

Estado de implementación a 2026-10-08: [piloto de Urantia](evidencias/presentaciones-2026-10-08/piloto.md) implementado y validado localmente; staging pendiente. En esta etapa se usa entrega privada mediante PHP y variantes de hasta 3840 píxeles. X-Accel-Redirect y el adaptador de apps descritos más adelante siguen siendo el diseño objetivo, no capacidades desplegadas.

## 2. Evidencia actual

Fuentes: índice de Iván de 2026-10-01, ocho PDF locales, código de este repositorio en `main`/`325fb19`, consulta de NocoDB con transacción SQL `READ ONLY` y consultas WP-CLI desde el host a staging. Informe de archivos: [evidencia local](evidencias/presentaciones-2026-10-01/inventario.json).

- Los ocho archivos indicados existen: 15 páginas cada uno, 120 en total, tamaño del lote inicial de aproximadamente 20,38 MB de PDF (anterior a las sustituciones). Las páginas son horizontales, con proporción aproximadamente 16:9.
- Urantia, Seth, Ramtha, Conversaciones con Dios y Círculo Carmesí tienen texto extraíble y fuentes incrustadas. Esto no implica que todos sus gráficos sean vectoriales.
- Las versiones originales de Un curso de milagros, Kryon y Un curso de amor eran imágenes de 1376 × 768 sin texto ni fuentes. Por indicación de Aitor se han sustituido por las nuevas versiones con texto y fuentes incrustadas, y también se ha sustituido el Círculo Carmesí por su nueva versión. Los cuatro PDF de Dropbox se trasladaron a las mismas rutas finales, con SHA-256 verificado antes de retirar el origen; las cuatro versiones anteriores se eliminaron. Las ocho presentaciones actuales de Series CDE conservan texto y fuentes. [Registro de sustitución e inventario actual](evidencias/presentaciones-2026-10-01/sustitucion-alta-resolucion.json). La [comparación previa](evidencias/presentaciones-2026-10-01/resolucion-originales-y-dropbox.md) es evidencia histórica.
- Se inspeccionaron visualmente las páginas 1, 7 y 11 de cada presentación del lote inicial: 24 muestras; esta revisión no cubre las cuatro versiones sustituidas. No es una revisión visual exhaustiva de las 120 páginas ni una validación editorial. La densidad de varios textos exige zoom y vista ampliada en móvil.
- Se calcularon hashes SHA-256 locales; no se compararon con copias canónicas de Iván porque el índice no proporciona sus hashes. La mención a Ramtha v4.0 procede del índice, no de una verificación independiente del número de versión.
- NocoDB: base `pl6ycwjk7ewg6af`, tabla `mb5v3xrgf1tcze5`, `cde_lecciones`. Las ocho claves existían exactamente una vez y no había campos de presentación en la consulta inicial. Posteriormente, el 2026-10-01, Aitor creó `presentacion_pdf_ruta`; una nueva lectura del esquema confirma el tipo `SingleLineText`. La evidencia JSON conserva la fotografía inicial, anterior al alta del campo.
- Staging: host `espacio-sutil-staging`, release `/srv/www/espaciosutil.org/releases/20261001070650`; Swiper instalado `12.1.4`. El directorio del release no conserva `.git`, por lo que no se atribuye el SHA local al servidor.
- Las series 01–04 tienen post de staging publicado y activo con la clave exacta. Las series 05–08 no tienen correspondencia por clave; sus IDs guardados en NocoDB tampoco existen en staging y no se encontraron candidatos por los títulos consultados. No importar sus presentaciones hasta preparar sus lecciones de destino.

| Serie | Clave canónica | Fila NocoDB | ID WP registrado en NocoDB | Correspondencia staging | Tipo de PDF |
| --- | --- | --- | --- | --- | --- |
| Urantia | `cde:01:00:0.1` | 1 | 2389 | 2389, publicado y activo | Texto extraíble |
| Seth | `cde:02:00:0.1` | 46 | 2586 | 2586, publicado y activo | Texto extraíble |
| Un curso de milagros | `cde:03:00:0.1` | 38 | 2770 | 2770, publicado y activo | Texto extraíble (versión nueva) |
| Ramtha | `cde:04:00:0.1` | 67 | 2814 | 2814, publicado y activo | Texto extraíble |
| Kryon | `cde:05:00:0.1` | 137 | 2962 | Sin correspondencia | Texto extraíble (versión nueva) |
| Conversaciones con Dios | `cde:06:00:0.1` | 156 | 2958 | Sin correspondencia | Texto extraíble |
| Un curso de amor | `cde:07:00:0.1` | 189 | 2965 | Sin correspondencia | Texto extraíble (versión nueva) |
| Círculo Carmesí | `cde:08:00:0.1` | 203 | 2967 | Sin correspondencia | Texto extraíble |

Los IDs de NocoDB son evidencia de su inventario, no una comprobación fresca de los posts de producción. La identidad de importación siempre es la clave, no el ID ni una coincidencia aproximada del título.

El lote se trasladó posteriormente a `<carpeta_local>/Presentación/<nombre original>.pdf`, conservando sus bytes y verificando las ocho copias antes de retirar los originales de Dropbox. [Evidencia del traslado](evidencias/presentaciones-2026-10-01/traslado-verificado.json). El inventario reconoce exclusivamente ese contenedor, para no confundir presentaciones con PDFs de lectura; su implementación está aislada y las rutas todavía no se han aplicado a NocoDB por esta tarea. [Estado de desarrollo e integración](evidencias/presentaciones-2026-10-01/estado-rama.md).

## 3. Alternativas de backend

| Estrategia | Ventajas | Costes o limitaciones | Decisión |
| --- | --- | --- | --- |
| Repetidor ACF con imagen privada anidada | Familiar; permite edición manual y ordenación | Requiere un campo privado y proteger las ediciones ante nuevas importaciones | Elegida, con filas iniciales del paquete importado |
| Galería ACF ordenada | Más sencilla que un repetidor cuando solo hay imágenes; selección múltiple y reordenación | Mantiene el trabajo de importación; datos por página y protección de adjuntos necesitan desarrollo adicional | Mejor alternativa manual si se descarta el importador |
| PDF → paquete generado + repetidor ACF + Swiper | Importación repetible, edición posterior y contrato común con apps | Requiere conversor, importador y gestión de las referencias editables | Implementada; editor editable validado en local |
| Visor PDF en navegador o PDF.js | Conserva zoom vectorial donde existe y evita variantes de imágenes | Otra experiencia de navegación y carga; no resuelve de forma homogénea las apps nativas ni los PDF rasterizados | No elegido para el slider solicitado |
| CPT de presentaciones o tabla nueva por diapositiva | Útil con varias presentaciones reutilizadas entre lecciones | Relaciones y edición innecesarias para una presentación por lección | Posponer hasta que exista esa necesidad |

El repetidor se adopta por la necesidad de gestionar manualmente las imágenes, sin exigir anotaciones o marcas temporales. Su campo de imagen propio mantiene el almacenamiento privado; no usa adjuntos públicos de WordPress.

## 4. Responsabilidades e identidad

- Iván: entrega editorial definitiva en PDF y comprobación de su diseño. No se exige PPTX ni se corrige contenido durante la conversión.
- Dropbox `AITOR CDE`: origen de entrega. El TXT sirve como aviso de lote y evidencia histórica, no como inventario mantenido en paralelo. Antes de registrar la presentación, el PDF debe estar en su ubicación final dentro de `Series CDE`.
- NocoDB `cde_lecciones`: referencia al PDF definitivo mediante un único campo de ruta. Los detalles técnicos de conversión y despliegue no se duplican en esta tabla.
- Archivo local: el PDF definitivo vive en la ubicación canónica de su lección en `Series CDE`; conservar su revisión por SHA-256 junto a la evidencia de conversión; no depender de que Dropbox mantenga el nombre o la revisión anterior. Paquete desplegable privado: solo manifiesto, imágenes y metadatos necesarios para el visor, sin PDF. Las revisiones anteriores de esos paquetes permiten revertir en el servidor sin disponer allí del documento fuente.
- WordPress: revisión activa, activación de la funcionalidad y acceso según la lección. Las apps no acceden directamente a NocoDB ni Dropbox.

Una lección tiene cero o una presentación activa en v1. La clave es `cde:SS:BB:codigo`, sin punto terminal en el código. Los nombres de carpetas se conservan; Unicode NFC/NFD y variaciones de puntuación se normalizan solo para comparar, sin renombrar fuentes automáticamente. Si una clave devuelve cero o varios posts/filas, el importador bloquea esa operación y pide resolver el mapeo en el informe.

## 5. Inventario mínimo en NocoDB

Campo ya creado por Aitor y confirmado en el esquema vivo el 2026-10-01; reutilizarlo en la fila existente de `cde_lecciones`:

| Campo existente | Tipo | Uso |
| --- | --- | --- |
| `presentacion_pdf_ruta` | SingleLineText | Ruta del PDF definitivo en su ubicación final, relativa a la raíz `Series CDE`. |

Raíz final: `/Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos/Series CDE`. El valor comienza por la carpeta de serie e incluye el nombre del PDF, sin prefijo absoluto ni `Series CDE/`; se resuelve con `series_root / presentacion_pdf_ruta`. No guardar la ruta de Dropbox ni un destino previsto aún inexistente. El PDF permanece en esta biblioteca final; no adjuntarlo otra vez en NocoDB. La clave existente identifica la lección y la nueva ruta identifica su presentación. No añadir checkbox de disponibilidad, número de páginas, SHA-256, fechas de verificación ni campos de despliegue por entorno.

- Ruta rellena significa «PDF registrado para esta lección»; la automatización comprueba que el archivo se puede leer cuando lo necesita. No presupone que la presentación esté publicada.
- Ruta vacía significa «sin PDF registrado», no una ausencia física demostrada. Si la automatización encuentra un PDF único ya incorporado a su ubicación final en `Series CDE`, puede proponer registrar su ruta; si el encargo exige presentación y no puede resolverla, lo comunica como incidencia.
- Si el archivo se mueve, actualizar la referencia. Un fallo temporal de acceso a la biblioteca final no borra la ruta ni convierte la fuente en ausente. Los errores de recepción desde Dropbox se resuelven antes de registrar el destino final.
- Si se sustituye el archivo conservando la ruta, el procesador detecta el cambio al comparar el contenido con su manifiesto local. Esa comprobación no necesita una columna SHA-256 en NocoDB.

El número de páginas y los hashes se calculan automáticamente durante la conversión y se conservan en el manifiesto técnico para comprobar integridad, evitar repeticiones y detectar sustituciones. WordPress conserva la revisión activa de cada entorno. Los informes de ejecución registran el resultado de la importación. Ninguno de esos datos exige mantenimiento editorial ni columnas adicionales en NocoDB.

Vista opcional «Presentaciones»: clave, serie, título y ruta, con filtro de ruta rellena. No prometer desde esta vista un estado de publicación: se consulta en WordPress o en el informe del proceso cuando haga falta.

Para la carga inicial, resolver primero el depósito de los ocho PDF en las ubicaciones finales de sus lecciones en `Series CDE`; esta actualización documental no los traslada ni afirma que ya estén allí. Preparar después las asignaciones de ruta a las filas identificadas, verificar los archivos finales y preservar cualquier valor previo distinto para revisión. Reutilizar el campo `SingleLineText` existente y actualizar mediante API/UI, con prelectura y comprobación posterior. No escribir por SQL, crear filas nuevas ni tocar `id_post_wp`, `url_wp`, `wp_status_verificado`, fechas editoriales o workflows. La futura automatización leerá esta referencia; cualquier escritura automática de ruta deberá incorporarse de forma acotada al mecanismo canónico del repositorio de contenidos, sin ampliar ahora su skill ni su policy.

## 6. Contrato de entrada y conversión

Entrada: PDF definitivo de la ubicación final en `Series CDE`, clave de lección explícita y raíz `series_root` configurada. Dropbox es el origen de recepción, no la fuente operativa de la conversión ni el destino del campo NocoDB. Una página equivale a una diapositiva, en el mismo orden. No es obligatorio que los futuros PDF tengan 15 páginas ni 16:9; se conserva su geometría. No editar texto, recortar para llenar el contenedor, reconstruir diseños ni exigir PPTX.

Preflight: comprobar PDF real mediante parser, lectura completa, ausencia de contraseña, páginas, dimensiones, rotación, límites y SHA-256. Como límites iniciales del importador se proponen 100 páginas, 100 MiB de entrada y 1 GiB de paquete; son límites configurables de implementación, no características de los PDF actuales. Rechazar rutas absolutas, enlaces simbólicos, `..`, entradas duplicadas o destinos fuera de la raíz si se usa un contenedor ZIP. Aplicar tiempo y memoria máximos por conversión. La conversión y, en la estrategia de derivados propuesta, las variantes responsive se ejecutan en el Mac; el servidor recibe únicamente imágenes y metadatos ya preparados. No instalar ni ejecutar un conversor PDF o una cola de conversión en WordPress. Validar por lista permitida el contenido del paquete remoto y rechazar PDF u otros originales aunque estén renombrados. El servidor verifica los hashes de las imágenes y la estructura; el hash del PDF vincula la evidencia local, no pretende ser una comprobación de un PDF alojado en remoto.

Derivados iniciales:

- PDF con texto/vector: anchos 640, 1280, 1920, 2560 y 3840 píxeles, manteniendo proporción. La mayor versión sirve para ampliar. El límite superior se revisará con la prueba de legibilidad; no garantiza detalle adicional en fotografías incrustadas.
- Para futuras fuentes rasterizadas de resolución similar a los originales retirados: anchos 640 y 1280; no generar 1920/2560 por interpolación. Conservar el PDF original únicamente en el archivo local para trazabilidad. La resolución efectiva se determina por página, no por los puntos del MediaBox.
- WebP como formato inicial, eligiendo compresión mediante comparación visual con PNG de referencia. No fijar una calidad universal antes del piloto: texto fino y fondos complejos requieren comprobación. No generar AVIF ni varios formatos alternativos en v1 salvo una incompatibilidad demostrada.
- Las variantes son responsabilidad del conversor; no hacer que WordPress vuelva a redimensionarlas ni depender del umbral global de imágenes de WordPress.
- Las diapositivas se presentan como imágenes, sin extracción de texto ni OCR, incluso cuando el PDF contenga texto extraíble. Los resúmenes existentes en la lección acompañan el estudio; no se genera una transcripción adicional por diapositiva ni se incluyen campos de texto extraído en el paquete o la API.

WordPress tiene soporte nativo de `srcset`/`sizes` para adjuntos públicos ([documentación](https://developer.wordpress.org/apis/responsive-images/)); aquí se construyen a partir del manifiesto para usar rutas privadas y mantener las mismas variantes disponibles en la app.

### Paquete inmutable

Separar el archivo local de fuentes del paquete desplegable. El primero conserva el PDF por `source_sha256` y los informes de conversión fuera del directorio de exportación. El segundo usa la ruta lógica `{clave_codificada}/{revision}/` y contiene únicamente `manifest.json` y `slides/0001-640.webp`, etc. La hoja de contacto completa y demás evidencias técnicas quedan locales salvo que el visor necesite un derivado concreto. La copia al servidor enumera exclusivamente el manifiesto y sus imágenes: nunca sincroniza recursivamente la carpeta que contiene el PDF. El manifiesto interno contiene `schema_version`, `clave_leccion`, `source_sha256`, `renderer_version`, `conversion_profile`, `page_count`, geometría por página y, por variante, ruta relativa, ancho, alto, MIME, bytes y hash.

`revision` es SHA-256 de un JSON canónico de clave, hash fuente, versión/perfil de conversión, orden, geometría y hashes de variantes. Se excluyen el propio identificador, fechas, URLs del entorno y cualquier credencial. El empaquetado usa orden y codificación deterministas. Dos importaciones del mismo paquete tienen el mismo identificador; si cambia el conversor o los derivados, el PDF puede conservar hash pero la revisión cambia.

La conversión comprueba todas las páginas y produce hoja de contacto. La revisión editorial del PDF no sustituye la comprobación de que los derivados no tienen páginas omitidas, recortes, artefactos de compresión o texto ilegible.

## 7. WordPress y ACF

Crear mu-plugin `espaciosutil-cde-presentations.php` con módulos propios para repositorio de paquetes, importación, validación, autorización y serialización. Reutilizar la autorización de lecciones y sesiones existente; no duplicar un motor de membresías. La integración debe declarar su dependencia y denegar si la capa de autorización no está disponible.

La pestaña «Presentación» pertenece a `group_cde_fields`, junto a los demás contenidos de la lección. El grupo conserva REST para sus campos existentes; un filtro excluye los campos de presentación y el campo privado tampoco se expone en REST.

| Dato | Persistencia | Edición |
| --- | --- | --- |
| Activación | `lesson_presentation_enabled` | Editor autorizado |
| Paquete inicial | `_cde_presentation_revision` | Importador, sin campo libre |
| Clave | `clave_leccion` existente | Identidad validada, sin inventarla |
| Diapositivas | Repetidor `lesson_presentation_slides`, clave `field_cde_presentation_slides` | Añadir, sustituir, quitar y arrastrar filas |
| Imagen por fila | `image`, clave `field_cde_presentation_image`, tipo `cde_private_image` | Referencia privada de la misma lección |

Sin metadatos del repetidor, las filas se obtienen del paquete importado. Guardar las filas establece la composición; un repetidor explícitamente vacío permanece vacío. Las filas idénticas al paquete inicial no se consideran una modificación manual. El servicio lee los metadatos sin depender de `get_field()` ni del tema activo.

No se suben PDF desde wp-admin. Las imágenes manuales generan sus variantes privadas con WordPress, sin publicar originales en `uploads`. El PDF y su ruta en NocoDB siguen representando la fuente editorial; no se reescriben al editar WordPress.

El importador comprueba revisión esperada y bloquea importaciones simultáneas por lección. Si hay edición manual, activar un paquete requiere `--replace-editor-slides`; así una automatización no puede borrar cambios del editor inadvertidamente. No se presupone que las skills actuales incluyan esta operativa. El guardado ACF conserva el bloqueo editorial estándar de WordPress; no comparte una transacción con el importador CLI.

Las revisiones y archivos anteriores permanecen privados. Para revertir una edición se conservan las filas, el puntero al paquete y la activación; restaurar solo el puntero no basta cuando existen filas manuales. El visor recarga el manifiesto al encontrar una revisión sustituida. [Implementación y pruebas del editor](cde-presentaciones-editor.md).

## 8. Acceso y archivos privados

Los archivos en `uploads` normalmente tienen URL directa: restringir el bloque Blade o guardar un ID en ACF no los protege. La propuesta almacena únicamente imágenes, miniaturas y manifiesto interno en `/srv/www/espaciosutil.org/shared/cde-presentations/`, fuera de `web/`, con persistencia entre releases. Staging y producción tendrán raíces y recibos independientes. El PDF fuente no forma parte de ninguna de esas raíces.

La presentación hereda exactamente la autorización de su lección: no existen nivel de membresía, permiso ni lista de acceso propios del slider. El backend reutiliza la comprobación de acceso de la lección antes de entregar cada recurso y verifica, además, que la revisión solicitada existe y está disponible. La activación de una presentación es una decisión de disponibilidad editorial, no un permiso adicional para el alumno. En web se debe preservar la regla actual de acceso por lección; la API móvil conserva además su requisito de membresía CDE. No introducir silenciosamente una restricción de membresía global en lecciones que la web permita consultar de forma pública según PMPro. Si PMPro o la autorización faltan, denegar.

Propuesta de entrega: controlador WordPress que autoriza y responde con `X-Accel-Redirect` a un `location internal` de Nginx. Es una capacidad documentada de Nginx ([referencia](https://nginx.org/en/docs/http/ngx_http_core_module.html#internal)), no una configuración ya aplicada. Para desarrollo local puede existir entrega PHP acotada; staging valida el recorrido Nginx real.

Ruta web propuesta de imagen: `/cde-presentations/v1/lessons/{id}/revisions/{revision}/slides/{page}/{width}`. Autenticación por cookie WordPress, con comprobación en cada GET/HEAD; se usa una ruta de lectura propia para que `<img>`/`srcset` no requieran cabecera de nonce REST. El backend resuelve los números contra el manifiesto y nunca toma del cliente una ruta de disco. Vista previa administrativa separada, protegida por capacidad `edit_post`, sin alterar el acceso del alumno.

Las apps usarán su Bearer existente en un endpoint binario bajo `cde-mobile/v1`. El token va en cabecera, nunca en URL. No mezclar cookie web y Bearer en una misma política ambigua. No añadir URLs públicas permanentes ni un segundo proveedor de almacenamiento en v1.

Respuestas privadas: `Cache-Control: private, no-store`, sin caché compartida de CDN, página o service worker. Esto no borra bytes ya recibidos por un usuario autorizado; la revocación se exige para nuevas peticiones. Verificar también respuestas condicionales/HEAD y no devolver 304 antes de autorizar. La app futura no implementará descarga offline en esta fase.

Los campos internos no se exponen por ACF REST, API genérica de medios, HTML público, feeds, JSON-LD ni listados. No registrar estos paquetes como adjuntos ordinarios públicos. No hay PDF en el servidor ni endpoint para descargarlo; la fuente se conserva únicamente en origen y en el archivo local.

## 9. Contrato de lectura para web y apps

El servicio de dominio devuelve datos independientes de Swiper, ACF y URLs físicas. El adaptador web compone el Blade con el mismo modelo; el endpoint móvil propuesto es `GET /wp-json/cde-mobile/v1/lessons/{lessonId}/presentation`. Implementar y probar el endpoint en staging en esta fase permite preparar las apps sin modificar sus pantallas todavía.

Respuesta ilustrativa abreviada (una diapositiva; las revisiones/hashes se representan aquí con etiquetas, no con valores válidos):

```json
{
  "schema_version": 1,
  "lesson_id": 2389,
  "clave_leccion": "cde:01:00:0.1",
  "available": true,
  "presentation": {
    "id": "cde:01:00:0.1:presentation",
    "revision": "<sha256-del-paquete>",
    "title": "Presentación de la lección",
    "page_count": 1,
    "slides": [
      {
        "id": "<revision>:0001",
        "page": 1,
        "alt": "Diapositiva 1 de 1 de Qué es El libro de Urantia",
        "sources": [
          {
            "width": 1280,
            "height": 720,
            "mime_type": "image/webp",
            "bytes": 150000,
            "url": "https://stage.espaciosutil.org/wp-json/cde-mobile/v1/lessons/2389/presentation/revisions/<revision>/slides/1/1280"
          }
        ]
      }
    ]
  }
}
```

Invariantes:

- `schema_version=1`; `revision` real de 64 caracteres hexadecimales; IDs estables dentro de una revisión. Cambiar de revisión invalida las referencias anteriores.
- `page_count` coincide exactamente con `slides.length`; páginas consecutivas `1..N`; variantes ordenadas por ancho, sin anchos duplicados. Ancho/alto/bytes son enteros positivos reales.
- Cada diapositiva conserva un `alt` de orientación con su posición y la lección. No incluye `text` ni `text_status`. Los resúmenes permanecen en el contenido de la lección, sin duplicarse en el manifiesto. Las etiquetas del visor y los resúmenes no implican una transcripción completa de las diapositivas.
- Lección accesible sin presentación activa: HTTP 200, `available=false`, `presentation=null`. Una revisión activa rota devuelve error de servicio y se registra; no fingir que simplemente no hay presentación.
- Sin sesión cuando sea necesaria: 401; sin permiso: 403; lección inexistente/no disponible: 404; revisión solicitada sustituida: 409 con código `presentation_revision_changed`; dependencia/paquete activo roto: 503.
- El endpoint móvil conserva el sobre de errores actual (`code`, `message`, `request_id`, `retryable`). Las URLs son absolutas, HTTPS y del entorno consultado; no exponen rutas locales o de Dropbox.
- El esquema nuevo se añade al registro de contratos; no cambiar de forma incompatible `Lesson` o `Study`, que usan `additionalProperties=false`. Las apps antiguas continúan usando los endpoints existentes. Un indicador en `Study` será una ampliación coordinada posterior si se necesita.
- Ver diapositivas no completa la lección ni modifica resultados de quiz o progreso de audio/vídeo. No hay sincronización temporal con los reproductores.

## 10. Frontend web

Ubicación propuesta: sección «Presentación de la lección» después del reproductor y antes del contenido escrito, dentro de la comprobación de acceso. Se muestra solo si hay revisión activa habilitada. Mantener el texto de estudio existente como recurso independiente.

Reutilizar Swiper `12.1.4`, instalado tanto localmente como en staging, con `Navigation`, `A11y` y `Zoom`; contador de página sencillo y teclado limitado al visor enfocado. No usar el teclado global mientras el alumno interactúa con el quiz o con otros controles. Swiper ofrece esos módulos y carga diferida basada en las imágenes del navegador ([API oficial](https://swiperjs.com/swiper-api)).

- Una página cada vez, sin autoplay, sin bucle y sin recortes (`object-fit: contain`). Anterior/siguiente deshabilitados en los extremos; un único slide no muestra navegación innecesaria.
- Deslizamiento táctil, botones etiquetados, contador «3 de 15», zoom y restablecimiento. Al ampliar, el desplazamiento de la imagen tiene prioridad sobre pasar de página.
- Vista ampliada mediante diálogo accesible que ocupa el viewport; Fullscreen API solo como mejora si está disponible. No depender de fullscreen ni bloqueo de orientación en Safari/iOS.
- Escape cierra, foco vuelve al botón de apertura, foco contenido en el diálogo, fondo inerte y bloqueo de scroll reversible. Preferencia de movimiento reducido respetada.
- No descargar las 120 imágenes ni todas las variantes al entrar en una lección. Primera imagen al acercarse/abrir el visor; diapositiva actual y vecinas; versión grande bajo demanda al ampliar. Medir solicitudes reales: no asumir que `loading=lazy` por sí solo evita todas las descargas de un carrusel.
- Definir `srcset`, `sizes`, `width`, `height` y espacio reservado según la geometría. Las miniaturas, si se añaden, reutilizan derivados pequeños y no se cargan de forma indiscriminada.
- Iniciar tras carga directa y tras navegación Barba. Destruir Swiper, listeners y diálogo al salir; inicialización idempotente. Integrar `app.js`, `transitionScripts.js` y el hook de salida de `barba.js`. El quiz actual no se reinicia en `transitionScriptsAfter`; no copiar ese hueco al nuevo visor ni ampliar esta tarea a rehacer el quiz.
- Error de imagen: mensaje y reintento, sin salto silencioso de páginas. Cambio de revisión: refrescar una vez; pérdida de sesión: cerrar el acceso y ofrecer volver a iniciar sesión.

Los tamaños responsive optimizan descarga y nitidez; no reorganizan el texto de la diapositiva. Las ocho fuentes actuales permiten rasterizar el texto a mayor resolución; los fondos conservan su límite de detalle original. No se cambiará su contenido ni se solicitará PPTX como requisito técnico.

## 11. Integración futura con la automatización de lecciones

Referencia de integración: [cde-lesson-full-flow/SKILL.md](</Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos/.agents/skills/cde-lesson-full-flow/SKILL.md>). Se ha leído como contexto; no se modifica ni ejecuta en esta tarea. El contrato local consultado es `cde.lesson_flow` 2.1.2. Su implementación futura debe seguir el mecanismo de evolución de `references/versioned-flow.md`, no limitarse a añadir una frase a la skill.

Una vez construidos y validados el conversor y la infraestructura de staging, incluir el siguiente recorrido en los nuevos flujos compatibles:

1. **Intake e inventario:** resolver la misma `clave_leccion` y localizar por evidencia un único PDF definitivo. Fijar en el manifiesto de ejecución su hash y ruta local; leer el PDF final de `Series CDE` a partir de la ruta registrada o de una resolución única dentro de esa biblioteca, sin tratar la mera referencia como prueba de publicación. La presentación es un recurso opcional salvo requerimiento explícito del encargo: ausencia concluyente registrada permite continuar sin ella; fuente prevista pero inaccesible, ambigua o inválida bloquea su dependencia y no se omite silenciosamente.
2. **Preparación local:** ejecutar una fase técnica de presentación después de resolver identidad/inventario. Convertir y validar imágenes, orden, variantes y manifiesto. Puede transcurrir en paralelo con el trabajo audiovisual/editorial porque no depende de MP4, VTT ni Bunny. La revisión verifica los derivados sin regenerarlos ni cambiar el contenido definitivo de Iván. Preservar PDF y evidencia local; exportar solo el paquete web.
3. **Publicación WordPress:** el mismo escritor de la fase WordPress importa, vincula y configura la presentación en el borrador de la lección, después de conocer el post y antes de `WP_VERIFIED`. Si el destino es `future`, todas las escrituras de presentación deben terminar antes de `FUTURE_APPLY_LAST_MUTATION`; no añadir un upload o activación tardíos después de programar. La presentación preparada en un borrador solo es previsualizable por el editor y no queda accesible al alumno antes de la publicación de la lección.
4. **Reconciliación y revisión final:** comprobar clave, revisión, número de páginas, integridad y acceso heredado; conservar estos resultados en el informe técnico y consultar la revisión activa en WordPress, sin añadir campos de presentación a NocoDB aparte de la ruta. Conservar la continuación existente para observar una publicación programada; no cerrar el flujo solo porque se hayan subido las imágenes.

Registrar capacidad versionada propuesta `presentations@1`, dependencia WordPress y evidencia de entrada/salida en el motor cuando esté implementada. El manifiesto fija `present`, `absent` o el error de resolución de la fuente; no deducir que el runtime soporta presentaciones por ausencia de PDF. Si el encargo las requiere y el motor no soporta la capacidad, bloquear en preflight. Congelar hash fuente y revisión de paquete: una sustitución del PDF durante el flujo requiere replanificación, sin sobrescribir una evidencia aceptada.

La integración se desarrolla como mantenimiento separado después del piloto, con actualización coordinada de skill, runtime, contratos, policy, pruebas y certificado. No modificar ahora la skill ni el contrato 2.1.2; no recertificar durante una lección ni migrar flujos en curso automáticamente. No habilitar producción por haber validado staging. Añadir o sustituir presentaciones de lecciones ya publicadas requiere una operación focal compatible con el contrato de sustitución, sin volver a ejecutar todo el full flow ni sortear sus restricciones actuales de sobrescritura.

## 12. Alcance, riesgos y evolución

Incluido en el primer incremento: conversor local, paquete, importador idempotente, pestaña ACF, entrega privada, visor web de staging, extensión aditiva del contrato/API y piloto real. Inventario NocoDB con un único campo de ruta, sin duplicar datos técnicos ni añadir automatización periódica nueva.

Fuera del primer incremento: despliegue en producción, interfaces Android/iOS, descarga offline, PDF descargable por alumnos, sincronía con vídeo/audio, progreso de diapositivas entre dispositivos, traducciones, extracción de texto y OCR, edición de contenido, varias presentaciones por lección y nueva CDN.

Riesgos que el piloto debe resolver: legibilidad de fuentes pequeñas; fidelidad de compresión; protección de cada imagen; concurrencia al cambiar revisión; navegación Barba y convivencia con quiz; fuentes nuevas con mismo nombre; cuatro destinos ausentes en staging. La futura app reutiliza el contrato y los derivados, pero necesita pruebas propias de gestos, caché de sesión, resolución y accesibilidad.

Plan ejecutable: [implementación en staging](planes/cde-presentaciones-staging.md).
