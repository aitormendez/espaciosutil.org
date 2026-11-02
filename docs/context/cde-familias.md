# Familias de series del CDE

## Alcance y fuente canónica

Implementación backend independiente del tema en [el MU-plugin de familias](../../site/web/app/mu-plugins/espaciosutil-cde-families.php) y [su servicio PHP](../../site/web/app/mu-plugins/cde-families/families.php). No cambia presentación, membresía, medios ni permisos de acceso a lecciones.

| Slug estable | Etiqueta exacta |
| --- | --- |
| `espirituales-esenciales` | Enseñanzas espirituales esenciales |
| `otras-principales` | Otras enseñanzas del grupo principal |
| `otras-espirituales` | Otras enseñanzas espirituales |
| `informacion-complementaria` | Información complementaria |

- `familia_serie` es una taxonomía de catálogo sin object types: no asignable a posts ni términos mediante relaciones de posts. WordPress no tiene relaciones nativas taxonomía→término.
- La única asignación canónica es el **term meta `familia_serie` de la raíz `serie_cde`**, cuyo valor es uno de estos slugs, no el ID del término familiar.
- La taxonomía `serie_cde` conserva raíces=series, hijos=bloques. Se permiten descendientes de bloques.
- En la automatización actual, el post índice/inactivo del bloque tiene `serie_cde`; las lecciones heredan por `post_parent`. El resolver recorre todos los ancestros, no sólo el inmediato.
- Una lección puede tener términos propios de la misma raíz. Si ella o cualquier ancestro tienen raíces diferentes, se devuelve error incluso cuando las familias coinciden. No hay prioridad silenciosa que esconda contradicciones.
- Familia en un bloque, familia desconocida, metadatos duplicados, padres inexistentes/no CDE y ciclos producen error. Ausencia de familia significa **pendiente de clasificación humana**, nunca una familia por defecto.
- No se deduce nada de códigos, nombres ni listas de series: las listas están abiertas; cuatro familias no significa cuatro listas cerradas de enseñanzas.

## Fuente editorial de clasificación

[Mensaje íntegro de Iván y tabla de correspondencias con los nombres cortos de Aitor](cde-familias-clasificacion-ivan.md). Fuente proporcionada directamente por Aitor el 02/11/2026 y adjunta en [el documento del issue de clasificación](https://mac-studio-de-aitor.tail1fa965.ts.net:8443/ESP/issues/ESP-3182#document-ivan-clasificacion). La asignación solicitada en WordPress se limita a series ya existentes en producción; no crear las ausentes de la lista ni clasificar Guía de estudio por inferencia. La fuente editorial no sustituye la comprobación del backend desplegado.

## Presentación de las familias en las lecciones

El tema obtiene la familia mediante `Families::resolveLesson()` en [el helper de presentación](../../site/web/app/themes/sage/app/helpers.php), sin consultar relaciones de posts en `familia_serie` ni duplicar las reglas de herencia. Las etiquetas cortas de la interfaz no modifican las etiquetas del catálogo backend.

| Slug canónico | Nombre visible | Icono Blade | Token de fondo |
| --- | --- | --- | --- |
| `espirituales-esenciales` | Serie esencial | `cdefamily-mercaba` | `--color-serie-esencial` |
| `otras-principales` | Serie principal | `cdefamily-torus` | `--color-serie-principal` |
| `otras-espirituales` | Serie espiritual | `cdefamily-triangle-spiral` | `--color-serie-espiritual` |
| `informacion-complementaria` | Serie complementaria | `cdefamily-cubes` | `--color-serie-complementaria` |

- El [distintivo de familia](../../site/web/app/themes/sage/resources/views/partials/lesson-family-badge.blade.php) aparece antes del título únicamente cuando la resolución devuelve una familia válida. Muestra el icono decorativo y la etiqueta visible.
- Sin asignación, con familia desconocida, conflicto o backend no disponible, no se muestra el distintivo y el fondo es `--color-cde`. No se inventa una clasificación.
- Sólo las lecciones `single-cde` sustituyen el color persistente del menú; su identidad y el resto de páginas no cambian. El atributo `data-section-color` referencia el token CSS. [Los estilos de lección](../../site/web/app/themes/sage/resources/css/layouts/lesson.css) cubren la primera pintura y conservan los tokens en Tailwind; la navegación resuelve las variables antes de animarlas y restaura el color familiar al cerrar el menú.
- Los SVG originales usan `currentColor`. El prefijo es `cdefamily`, sin guion interno por compatibilidad con Blade Icons. Ejemplo: `<x-cdefamily-mercaba class="h-12 w-12 text-sol" aria-hidden="true" />`. Ver [origen, geometría y crédito de los iconos](cde-familias-iconos.md).
- Implementación y pruebas en local: ELDU (raíz `serie_cde` 61) clasificada como esencial por petición explícita; no se clasificaron otras series. La BD local está desactualizada y esta asignación de prueba no sustituye la clasificación vigente en producción.
- Antes de publicar los símbolos en producción: confirmar su licencia y actualizar el Aviso legal vigente con el crédito Side Project / Noun Project y la excepción para licencias de terceros. No trasladar el texto de la BD local ni dar esta implementación local por desplegada.

Validación: `php site/tests/cde-families-frontend.php` cubre el mapa, estados sin familia y render real de los cuatro SVG; `sectionColor.test.js` comprueba los tokens frente a los colores existentes del menú. QA local en escritorio y móvil: ELDU, herencia anidada, Seth sin familia, navegación de ida/vuelta y restauración del fondo tras abrir/cerrar el menú. La navegación lección→índice usa Barba; el clasificador de rutas JS preexistente no incluye `/lecciones-del-cde/`, por lo que índice→lección recarga la página. Esta tarea conserva ese comportamiento.

## NocoDB y publicación

Contrato para Contenidos: campo `familia_serie` SingleSelect con los cuatro slugs anteriores. Valor humano protegido; automatización sólo lo lee. El publicador compara el valor con la familia heredada, sin asignar o reclasificar automáticamente la serie.

API PHP estable, clase `EspacioSutil\Cde\Families`:

```php
Families::catalog();                              // array de slug, label, term_id|null
Families::resolveSeries($termId);                 // array|WP_Error, acepta raíz o bloque
Families::resolveLesson($postId, $proposed = []); // array|WP_Error
Families::validatePublication($postId, $proposed, $expectedFamily); // array|WP_Error
Families::assignSeries($rootId, $slug, $replace = false);           // bool|WP_Error
Families::seedCatalog();                          // array|WP_Error; operación explícita
```

`$proposed` sólo interpreta `post_parent` (entero >= 0) y `serie_cde` (lista de IDs enteros positivos); omitir conserva datos existentes, `[]` elimina asignaciones propias para resolver el estado propuesto. `$postId=0` representa una lección aún no creada. El resolver y preflight no escriben datos.

Ejemplo de integración del publicador WP-CLI **antes de cualquier escritura**:

```php
use EspacioSutil\Cde\Families;

$result = Families::validatePublication(
    0,
    ['post_parent' => $blockIndexPostId, 'serie_cde' => []],
    $nocodbRow['familia_serie']
);
if (is_wp_error($result)) {
    WP_CLI::error($result->get_error_code().': '.$result->get_error_message());
}
// Sólo después: wp_insert_post(...), metadatos, medios, etc.
```

**Distinguir propuesta y estado guardado:** pasar `$proposed` sólo para validar una escritura de jerarquía que todavía no se ha realizado. Después de escribir, y al programar una lección ya guardada, usar `Families::validatePublication($postId, [], $expectedFamily)`: el array vacío verifica los padres y términos reales. Reutilizar la propuesta en readback puede ocultar un término propio conflictivo o un padre distinto. Comparar además la jerarquía y los IDs reales con el plan sellado.

El publicador externo basado en plan sellado debe integrar esta llamada y sellar también `familia_serie`, IDs de serie/bloque y estado previsto. No está modificado por este cambio WP. Revalidar justo antes de escribir: el preflight no bloquea concurrencia ni sustituye controles de idempotencia del publicador. Los usos directos de `wp_insert_post`, SQL o metadatos no quedan protegidos automáticamente por una validación REST.

Resolución correcta devuelve `familia_serie`, `label`, `series_id`, `series_slug`, `source_term_id`; para lecciones también `source_post_id` e `inherited`. `source_post_id` identifica el post más cercano con asignación de serie, no un identificador de NocoDB.

### Operaciones estructurales explícitas

Desde `site` en el **host**, no desde la VM:

```bash
wp cde families seed
wp eval '$r = \EspacioSutil\Cde\Families::assignSeries(123, "espirituales-esenciales"); if (is_wp_error($r)) { WP_CLI::error($r->get_error_message()); }'
```

El ID de serie es un ejemplo: obtener el real del inventario y aprobar su clasificación. **No se ha asignado familia a ninguna serie existente.** Sí se ejecutó la semilla dos veces únicamente en local/development: términos 84, 85, 86 y 87 en el orden del catálogo, sin duplicados en la segunda ejecución. Estos IDs son evidencia local, no constantes del contrato. La semilla crea sólo los cuatro términos de catálogo, es idempotente y no modifica etiquetas discrepantes; devuelve conflicto para revisión humana. Una clasificación ya existente no cambia salvo `$replace=true` explícito. Asignar no siembra automáticamente el catálogo; los slugs tienen significado incluso antes del seed. El catálogo semántico cerrado del código es la autoridad; los términos materializan ese catálogo para consumidores WP, no definen nuevos valores.

No escribir `familia_serie` directamente mediante metadatos o SQL. Esas escrituras evitan el setter; el resolver denuncia corrupción, no la repara. No hay tarea de inicialización, migración de lecciones, seed en `init` ni operación automática en producción.

## Administración humana

En **Lecciones del CDE → Series del CDE**, crear/editar una raíz muestra un selector con los cuatro nombres exactos. Requiere `manage_categories` y nonce; una familia nueva se guarda mediante el setter validado. Cambiar una familia ya asignada requiere marcar explícitamente la confirmación de reclasificación de la serie completa. Un selector vacío no borra asignaciones. Los errores se presentan como avisos administrativos; no se cambian datos silenciosamente.

En bloques, el mismo apartado sólo muestra la familia heredada o el error pendiente: no hay selector local. El catálogo sigue cerrado y no aparece una pantalla para crear familias arbitrarias. Crear una raíz y elegir familia es una operación humana explícita, no clasificación masiva.

## REST

Rutas bajo `/wp-json`:

- `GET /cde/v1/familias`: público, cuatro slugs y etiquetas; `term_id=null` mientras no se siembre. No expone contenido.
- `GET /cde/v1/familias/inventario?kind=series|lessons&page=1&per_page=100`: requiere `manage_categories` y `edit_posts`. Paginación 1..100, orden ascendente por ID, `total` y `total_pages`, `Cache-Control: private, no-store`. `kind=series` incluye raíces y bloques con padre; `kind=lessons` incluye publish/draft/pending/private/future, sin contenidos ni recursos privados. Cada fila contiene `family: {ok:true,...}` o `{ok:false,error:{code,message,data}}`: una serie pendiente no bloquea el inventario entero.
- `PUT /cde/v1/familias/series/{rootId}`: requiere `manage_categories`. Body `{"familia_serie":"espirituales-esenciales","replace":false}`. Sólo para asignación estructural humana; no para el publicador de lecciones.
- REST estándar `wp/v2/serie_cde` y `wp/v2/cde` incorpora el campo de lectura `familia_resuelta` con el mismo sobre `ok/error`. No se expone escritura REST del term meta.
- El hook `rest_pre_insert_cde` tiene **opt-in explícito**: sólo al enviar `familia_serie` verifica resolución y coherencia **antes de insertar**, incluso en borradores. No guarda esa familia en la lección. Sin ese parámetro preserva toda publicación/edición legacy, incluso cambios de padre: instalar el plugin no impone una migración editorial. El publicador nuevo debe enviar el campo REST o invocar siempre el preflight PHP por WP-CLI.
- REST rechaza convertir una raíz clasificada en hija de otra serie. El filtro WP de cambio de padre también conserva la raíz y muestra aviso en administración. Las operaciones estructurales deben usar las APIs validadas y revisar el inventario.

Errores propios tienen prefijo `cde_family_` y status 422: `unknown`, `unassigned`, `no_series`, `series_missing`, `lesson_missing`, `parent_invalid`, `terms_invalid`, `series_cycle`, `post_cycle`, `child_assignment`, `duplicate_meta`, `ambiguous_series`, `mismatch`, `root_required`, `reclassification_required`, `catalog_conflict`, `write_failed`. Los errores nativos de almacenamiento pueden conservar su código WP.

### Identificadores

El **slug familiar** es la clave estable y portable entre WP/NocoDB/entornos. IDs `term_id`, `series_id`, `post_id` y `source_post_id` son estables dentro de su instancia WP, no globales ni portables tras reimportar. El inventario añade `site_url` para distinguir instancias, slugs para diagnóstico y `clave_leccion` literal en lecciones. No garantiza unicidad de `clave_leccion`, no inventa una clave cuando falta y no usa títulos como identidad. Cambiar un título no cambia la clasificación.

## Verificación local

Desde `site`:

```bash
php tests/cde-families-unit.php
wp eval-file tests/cde-families-live.php
php -l web/app/mu-plugins/cde-families/families.php
# Opcional y exclusivamente development: crea fixtures y elimina sólo los propios.
CDE_FAMILIES_TEST_WRITE=1 wp eval-file tests/cde-families-integration.php
```

[Pruebas unitarias](../../site/tests/cde-families-unit.php): fixtures en memoria, semilla idempotente, herencia post/término, altas propuestas, conflictos incluso con igual familia, códigos no inferidos, ciclos, metadatos inválidos, setter estructural, preflight, opt-in REST sin regresión legacy, nonce/capabilities, selector administrativo, bloque readonly, reclasificación confirmada y protección raíz→bloque.

[Prueba WordPress local](../../site/tests/cde-families-live.php): sólo development, sólo lectura; catálogo, autorización, inventario/paginación/errores, campos REST y ausencia de siembra implícita. No crea usuarios, términos, series ni lecciones. WP-CLI funciona desde el host; PHP 8.5 muestra avisos preexistentes `PDO::MYSQL_ATTR_SSL_CA` en la configuración del tema, ajenos a familias.

[Prueba de integración con escritura local](../../site/tests/cde-families-integration.php): exige development y opt-in explícito. Crea dos raíces, dos bloques y tres borradores temporales de identidad única; prueba asignación real idempotente, herencia anidada, conflicto de series y cambio de familia entre preflight y readback. Elimina únicamente sus propios fixtures, verificando ID/tipo/nombre y estado antes de cada eliminación, incluso si una aserción falla. No publica ni programa contenido, no clasifica series existentes ni opera sobre producción. Ejecución del 02/11/2026: **21 comprobaciones correctas y limpieza completa**, incluyendo la distinción entre propuesta de corrección y estado persistido realmente conflictivo. Junto a 58 unitarias y 20 comprobaciones de sólo lectura, se han verificado 99 comprobaciones del backend.
