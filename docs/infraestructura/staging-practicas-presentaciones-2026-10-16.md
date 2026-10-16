# Despliegue conjunto de prácticas y presentaciones en staging

Fecha: 2026-10-16. Despliegue solicitado expresamente por Aitor y completado en `https://stage.espaciosutil.org`. Producción, el checkout operativo de Contenidos, los certificados y el catálogo/configuración de Paperclip no se modifican.

## Versión entregada

- Rama publicada: `codex/cde-staging-practicas-presentaciones`.
- Worktree de integración: `/Users/aitor/.codex/worktrees/cde-staging-practicas-presentaciones/espaciosutil.org`.
- Commit de código desplegado: `f1a816f2e0e5366acc82df9793d5bb8e4f61f6ff`.
- Release activo: `/srv/www/espaciosutil.org/releases/20261016080930`.
- Release previo conservado: `/srv/www/espaciosutil.org/releases/20261014172316`, procedente de `1431c8d`.
- Trellis: `ok=58`, `changed=29`, `unreachable=0`, `failed=0` en la ejecución final. Código y manifest compilado comprobados contra el release activo mediante SHA-256.

La rama parte de `main` (`4d0cc3e`) e integra prácticas hasta `6215293` y presentaciones/editor hasta `6c6d83a`, además de documentación y evidencias de la rama `codex/cde-presentaciones-documentacion`. Se conservan el marcado de lecciones vistas y la página 404 presentes en main. Los conflictos se resolvieron combinando inicializadores, campos y composición de secciones; no se sustituyeron archivos completos por versiones antiguas de las ramas.

Los módulos de presentación presentes en el checkout compartido se compararon con la rama del editor y coincidían. Los cambios ajenos de infraestructura local, sincronización, informes internos, volcados SQL y copias temporales no se incorporaron a la entrega. El checkout compartido y las ramas originales conservan su trabajo; `main` no se ha fusionado ni actualizado con esta entrega.

## Funcionalidad y datos

La versión incluye el catálogo y las fichas de prácticas, el visor compartido incrustado, las 49 pantallas de EP-0.1 con narración de Iván, el índice adaptable y la lectura completa. Incluye también el visor de 15 diapositivas de Urantia, su carga y ampliación, las clases Tailwind compiladas, el repetidor ACF para editar imágenes privadas y su protección frente a importaciones que sobrescriban una edición manual.

Presentación y Prácticas son pestañas del grupo «Lección CDE». La lección usa el fondo oscuro compartido y un separador por frontera entre los bloques presentes; faltas de práctica o presentación no generan secciones vacías ni filetes duplicados.

La práctica 2998 y su relación con la lección 2389 ya existían en la base de staging. Se verificó la igualdad del texto local y remoto, SHA-256 `96f46e4f233662623fc7a8e8d20d78f6ab6519a940210dec495c1c35c9ded239`. No se reimportaron ni sobrescribieron. La relación sigue siendo una ubicación temporal de diseño; no acredita procedencia editorial de EP-0.1 en Urantia.

La presentación conserva sus 15 diapositivas y la revisión `d831299f008ae8d911eceec756eb8399c570f6b878e52d1efa887712f70159f3`. Se comprobó el editor sin guardar la lección ni modificar el orden o las imágenes.

Se instaló la revisión de audio `302831ff6f73eb67fffd841b9f0fe8526f91ead30de5ed2cbbbb6de681887448`: 150 archivos, incluidos 49 MP3, con todas sus huellas verificadas después de la transferencia. El almacén privado `/srv/www/espaciosutil.org/shared/cde-practicas-audio` queda enlazado desde `private/cde-practicas-audio` en cada release por la configuración de Trellis. Los audios quedan fuera del directorio público y de Git. La primera ejecución detectó una referencia circular al resolver la ruta compartida de uploads; se corrigió a `web/app/uploads` antes de que esa ejecución modificara el servidor.

Se añadió «Prácticas» después de «Lecciones» dentro de «Curso» en el menú CDE de staging (menú 60, nuevo elemento 3009). La instalación no cambia los menús de producción.

## Corrección del filtro por serie

La prueba final del catálogo detectó que Urantia no tiene una taxonomía asignada directamente: hereda el bloque 62 y la serie 61 a través de su lección padre 2387. La consulta inicial sólo consideraba términos directos y devolvía cero prácticas al filtrar. Se corrigió `Catalog::practiceIdsForSeries()` para resolver también la jerarquía de lecciones, manteniendo la exclusión de lecciones inactivas, no publicadas o con contraseña. Cinco casos aislados cubren serie directa, bloques hijos, padre/abuelo, deduplicación, otras ramas y resultados vacíos o inválidos. La corrección quedó desplegada y el recorrido de búsqueda, filtro por Urantia y apertura de ficha pasó en la release final; no se alteran taxonomías editoriales para hacer pasar el filtro.

## Migración necesaria para el progreso del curso

La inspección de la lección detectó un HTTP 503 `storage_unavailable` en la lectura de progreso de vídeo. La base de staging no contenía la opción `cde_mobile_schema` ni las cuatro tablas auxiliares del dominio compartido, aunque el módulo ya estaba activo. Se verificó InnoDB y se ejecutó desde el anfitrión, mediante WP-CLI remoto, la migración existente `EspacioSutil\Mobile\Schema::install()`, limitada por el propio código al host de staging.

La migración crea sesiones, límites, revisiones y recibos auxiliares; no reemplaza los progresos canónicos de `usermeta`. `Schema::ready()` quedó en true y la recarga de la lección terminó con cero errores de consola. No se cambiaron credenciales ni niveles de membresía.

## Validación

- Build conjunto de Sage y bloques mediante los hooks de Trellis; sintaxis de los 12 archivos PHP de módulos y editores revisados correcta.
- 14 pruebas de paquetes de presentaciones, 23 comprobaciones de su importador, 4 pruebas Python de límites de audio y 12 pruebas JavaScript de lecciones vistas/progreso correctas; cinco casos de regresión del filtro por serie heredada.
- Aislamiento comprobado en los tres entornos mediante pruebas locales; en staging vivo, cron y correo desactivados, petición a Stripe bloqueada, runner asíncrono desactivado y noindex activo. La validación no ejecutó correos ni cobros.
- Runtime: CPT registrado, Relationship estable, ambas pestañas ACF, 15 filas de imagen privada, exclusión de las relaciones y filas de ACF REST, 49 pantallas y 49 audios disponibles con numeración verbalizada.
- HTTPS real: miembro con acceso al catálogo, ficha y audio; anónimo rechazado en audio (403), REST de prácticas (401), manifiesto e imagen de presentación (401). Audio e imagen autorizados devuelven 200 con `private, no-store` y bytes coincidentes con sus hashes.
- Navegador de escritorio: columna de 896 px alineada con la lección a ventanas de 1920/1440/1024 px, sin desbordamiento; índice automático y manual, foco de encabezados en lectura, pantalla completa y retorno. Presentación: navegación, ampliación, zoom y cierre. Audio: reproducción de pantalla actual y siguiente.
- Separadores: cuatro combinaciones de presencia de práctica/presentación y un caso de bloques vacíos/ocultos, además de inspección de la lección real; un filete por frontera, sin borde inferior duplicado en la práctica.
- Credenciales temporales de Composer ausentes del release. Las sesiones temporales creadas para QA se retiran al finalizar, conservando las sesiones preexistentes.

[Evidencias de runtime y navegador](../qa/practicas/staging-20261016/README.md). Los avisos preexistentes de dependencias y tamaño de bundles no impidieron la compilación. Estas comprobaciones no son aprobación móvil ni prueba completa de compras o de la aplicación nativa.

## Respaldo y reversión

Respaldo previo privado: `/srv/www/espaciosutil.org/shared/backups/cde-20261016/before.sql`, SHA-256 `3bc07381cfedfcdffc051d4e79205824906c0f76086d7bcbdaff821f106bd372`. El mismo directorio conserva `.env` del release previo como `release.env`, su ruta y los recibos del menú. No versionar estos archivos privados ni las sesiones de navegador.

Si hay que revertir código, usar el release anterior de staging y verificar el sitio. No restaurar toda la base ni borrar tablas de progreso o audios por defecto: pueden existir avances posteriores. El menú se revierte de forma selectiva usando `menu-before.json` y el ID de `menu-added.json`, tras comprobar que nadie lo haya editado desde entonces. La migración auxiliar no necesita destruirse para volver al código anterior. La restauración integral del backup no se ha ensayado en esta operación.

## Acceso y siguientes pasos

- [Lección con práctica y presentación](https://stage.espaciosutil.org/lecciones-del-cde/urantia/que-es-el-libro-de-urantia/).
- [Catálogo de prácticas](https://stage.espaciosutil.org/practicas/).
- [Ficha de la práctica](https://stage.espaciosutil.org/practicas/sentir-el-suelo-prueba-local/).

Se mantiene el acceso público de demostración de staging con los permisos de contenido de WordPress; no se ha cambiado Nginx ni ampliado exposición. En este Mac, la conexión SSH usa un transporte temporal por la interfaz física porque la ruta de Tailscale no respondía; no se modificaron rutas ni cortafuegos.

El despliegue web no integra el inventario NocoDB, el motor de Contenidos o sus skills. La promoción a producción y la coordinación con esos sistemas siguen pendientes de autorización y planificación separadas. La documentación de este informe puede quedar en un commit posterior al código desplegado, porque `docs/` no forma parte del subtree web de la release.
