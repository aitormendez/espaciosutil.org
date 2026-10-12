# Prácticas CDE: implementación candidata e integración diferida

Fecha de implementación: 2026-10-01. Actualización documental: 2026-10-12. Estado: primera versión de texto implementada y probada en worktrees independientes; instalación y conexión a servicios pendientes de coordinación.

## Alcance implementado

- Mu-plugin `espaciosutil-cde-practicas.php` y módulos `cde-practicas/catalog.php` e `import.php`: CPT `practica`, rutas `/practicas/` y `/practicas/<slug>/`, campos ACF y acceso por cualquier membresía PMPro activa. Los editores pueden previsualizar contenido.
- Ficha: título y cuerpo del editor de WordPress, extracto para el catálogo, `clave_practica`, `codigo_practica` y `referencia_fuente`. Las lecciones relacionadas se calculan por consulta inversa; no hay lista manual duplicada. Se ordenan por orden de lección y título; el catálogo por título.
- Campo ACF `cde_practicas`, tipo Relationship, en las lecciones. El bloque «Prácticas de esta lección» muestra instrucciones desplegables y enlaza a la ficha. Excluye prácticas no publicadas o con contraseña. La consulta inversa excluye lecciones no publicadas, inactivas o con contraseña.
- Contexto CDE y navegación completa en `/practicas/`; se evita la transición Barba en estas rutas. No se modifican los menús de la base de datos.
- Acceso protegido en vistas y REST, exclusión del buscador general y sitemap, oEmbed deshabilitado y respuestas privadas sin caché. Durante la integración, excluir `/practicas/` y sus rutas REST de cualquier caché externa que pueda servir HTML antes de ejecutar WordPress.
- Importador WP-CLI: plan de solo lectura seguido de apply explícito. Resuelve identidades exactas, detecta duplicados y cambios posteriores al plan. Crea borradores y añade asociaciones confirmadas, sin publicar, retirar asociaciones ni sobrescribir textos existentes distintos. Conserva campos de lecciones ajenos al Relationship y no escribe en NocoDB.

## Acuerdo editorial posterior: pantallas y orden de EP-0.1

El 2026-10-12 Aitor aportó el acuerdo de Iván para una pantalla por titular y por párrafo, avance manual y ejecución al final tras recibir el contexto. El Markdown de lectura en el worktree de Contenidos se ha reordenado y renumerado, conservando íntegra la prosa. Consultar [las decisiones de diseño](practicas-cde.md). La voz sintética sigue como posibilidad por evaluar. El MVP de esta rama continúa mostrando texto: no incorpora todavía pantallas ni narración, y el importador/preparador no consume esa adaptación Markdown.

## Preparación en Contenidos

El worktree hermano añade `scripts/cde_practicas.py`, su JSON Schema, pruebas y la skill local `cde-practicas`. Recibe un export normalizado de prácticas y lecciones, comprueba fuentes y revisiones y emite un informe de pendientes y un paquete de WordPress. El TXT se convierte preservando viñetas y escapando HTML; no se generan instrucciones nuevas.

La integración directa con la API y el esquema vivo de NocoDB no está implementada. Esta primera versión trabaja con el export normalizado definido en `schemas/cde/practices-inventory.schema.json`; crear/mapear tablas y automatizar export/write-back requiere una instalación posterior basada en el esquema real. No presentar el preparador local como una sincronización remota completa.

Los campos técnicos de WordPress del post de la propia práctica aparecen en el recibo del importador. Su registro en NocoDB queda fuera del apply web. Las asociaciones solo se añaden: retirar una relación o actualizar una ficha que ya difiere exige una operación editorial separada. No se confunde un export parcial con una lista completa que autorice borrados.

## Comandos después de la instalación autorizada

Ejecutar WP-CLI desde el host en `site`:

```bash
wp cde-practicas plan /ruta/wordpress-package.json --out=/ruta/plan-nuevo.json --user=ADMIN
wp cde-practicas apply /ruta/plan-nuevo.json --user=ADMIN
```

El segundo comando modifica WordPress; ejecutarlo únicamente en un entorno autorizado después de revisar el plan. Usa un bloqueo de importación para evitar dos apply simultáneos. Si el proceso se interrumpe, revisar las escrituras parciales y el bloqueo `cde_practicas_import_lock` antes de retirarlo y preparar otro plan. No realizar reintentos ciegos. La publicación sigue siendo una decisión editorial explícita.

## Aislamiento y atribución

- Web: `/Users/aitor/.codex/worktrees/cde-practicas/espaciosutil.org`, rama `codex/cde-practicas`, base `325fb19c3b60bd0ddf171566dd56dbd677aa03d8`.
- Contenidos: `/Volumes/D/Documentos D/Trabajo/Espacio Sutil/CDE (curso desarrollo espiritual)/Contenidos-worktrees/cde-practicas`, rama `codex/cde-practicas`, base `91d2849`.
- Único cambio previo atribuible a este hilo: `docs/practicas-cde.md` en la web. Se copió, se verificó contra su respaldo y se guardó en el commit `d9becd6` antes de retirarlo del checkout compartido. SHA-256 original: `d85ba11506ced20b0bac5cb05658e4c2550af29e44790dc9a9f68152c578a27d`.
- El cambio pendiente inicial de `Contenidos/docs/cde-lecciones-inventario-nocodb.md` trataba sobre presentaciones y se conservó sin modificar. No se trasladaron cambios ajenos.
- La comparación posterior detectó cambios concurrentes en presentaciones y en el motor/certificado de Contenidos; no fueron realizados por este hilo. No se restauran ni se mezclan en estas ramas. Por ello no se declara que el checkout compartido permanezca idéntico a la fotografía inicial.

## Implicaciones para el motor y las skills

El grafo, la configuración y el certificado del motor no se cambian en estas ramas. La skill nueva no está instalada en Paperclip ni añadida a su catálogo. El preparador no llama al motor ni crea tareas. Los archivos nuevos de Contenidos sí entran en los patrones de huellas del certificado existente: integrarlos invalidaría esa fotografía del checkout.

Coordinar la integración cuando exista aislamiento por versión o una ventana compatible con las lecciones en curso. Comparar entonces ambas ramas con los nuevos commits del motor, resolver conflictos, ejecutar regresiones, certificar la versión elegida e instalar la skill de forma explícita. No recertificar el checkout operativo como atajo ni asumir que eso migra manifiestos ya creados.

## Pendiente de instalación y fases posteriores

1. Revisar el esquema vivo de NocoDB y aprobar su migración/mapeo; preparar el adaptador remoto sobre los campos reales.
2. Validar en staging con la versión exacta de WordPress y MySQL, ACF y PMPro del destino, incluyendo cachés externas.
3. Instalar ambos conjuntos de cambios de forma coordinada; regenerar reglas de enlaces permanentes una vez y añadir «Prácticas» al menú CDE desde WordPress. No ejecutar flush de reglas en cada petición.
4. Resolver en NocoDB la identidad técnica de la pieza `0.4.3.2 — La canalización como permiso y el ejercicio de sentir`, confirmada por Iván como origen de EP-0.1 y verificada en el inventario del 2026-10-08. Consultar la nueva ubicación y su huella en [las decisiones de inventario](practicas-cde.md). Después de la instalación autorizada, importar un borrador y revisarlo antes de publicarlo.
5. Incorporar posteriormente prácticas a la app Android/iOS y evaluar audio o guías por pasos según cada texto.

Evidencia y límites de las pruebas web: [validación de prácticas](qa/practicas/README.md). La regresión de Contenidos se ejecutó en modo candidato, sin emitir certificado: 1.546 pruebas y 5 auxiliares. La skill pasó validación estructural y una comprobación independiente con 17 casos CLI; los dos defectos encontrados quedaron corregidos con regresiones.
